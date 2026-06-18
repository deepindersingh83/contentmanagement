<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Update the current user's profile (name, email, phone, job title).
     */
    #[Route('', name: 'api_profile_update', methods: ['PATCH', 'PUT'])]
    public function update(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];

        if (array_key_exists('name', $data)) {
            $user->setName(trim((string) $data['name']));
        }
        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            $existing = $this->users->findOneByEmail($email);
            if ($existing !== null && $existing->getId() !== $user->getId()) {
                return $this->json(['message' => 'That email address is already in use.'], 422);
            }
            $user->setEmail($email);
        }
        if (array_key_exists('phone', $data)) {
            $phone = trim((string) $data['phone']);
            $user->setPhone($phone === '' ? null : $phone);
        }
        if (array_key_exists('jobTitle', $data)) {
            $jobTitle = trim((string) $data['jobTitle']);
            $user->setJobTitle($jobTitle === '' ? null : $jobTitle);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors->get(0)->getMessage()], 422);
        }

        $this->em->flush();

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'jobTitle' => $user->getJobTitle(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Change the current user's password (requires the current password).
     */
    #[Route('/password', name: 'api_profile_password', methods: ['PUT'])]
    public function changePassword(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent() ?: '{}', true) ?? [];
        $current = (string) ($data['currentPassword'] ?? '');
        $new = (string) ($data['newPassword'] ?? '');

        if (!$this->passwordHasher->isPasswordValid($user, $current)) {
            return $this->json(['message' => 'Your current password is incorrect.'], 422);
        }

        if (strlen($new) < 8) {
            return $this->json(['message' => 'New password must be at least 8 characters long.'], 422);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $new));
        $this->em->flush();

        return $this->json(['message' => 'Password updated successfully.']);
    }
}
