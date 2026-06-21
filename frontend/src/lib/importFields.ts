// Configuration for the Import settings (field-mapping) screen.
// `column` fields map a product attribute to a column from the uploaded file.
// `fixed` fields choose from a preset list. `checkbox` is a boolean.
// `tags` is a multi-select of values (optionally from a suggestion list).

export type FieldType = 'column' | 'fixed' | 'checkbox' | 'tags'

export interface FieldDef {
  key: string
  label: string
  type: FieldType
  hint?: string
  options?: string[] // for 'fixed' and suggestions for 'tags'
  group?: string // sub-heading within a category
}

export interface CategoryDef {
  key: string
  label: string
  icon: string // lucide icon name
  badge?: 'Extra' | 'New'
  locked?: boolean // Extra/New categories shown but not editable yet
  fields?: FieldDef[]
}

const SALES_CHANNELS = [
  'Online Store', 'Point of Sale', 'Shop', 'Google & YouTube', 'Pinterest',
  'Facebook & Instagram', 'TikTok', 'Inbox', 'Snapchat Ads',
]

export const CATEGORIES: CategoryDef[] = [
  {
    key: 'information',
    label: 'Information',
    icon: 'Info',
    fields: [
      { key: 'product_title', label: 'Product title', type: 'column' },
      { key: 'product_description', label: 'Product description', type: 'column' },
      { key: 'product_status', label: 'Product status', type: 'fixed', options: ['Active', 'Draft', 'Archived'] },
      { key: 'theme_template', label: 'Theme template', type: 'column' },
      { key: 'remove_current_tags', label: 'Remove Current Tags', type: 'checkbox' },
      { key: 'tags', label: 'Tags', type: 'column' },
    ],
  },
  {
    key: 'media',
    label: 'Media',
    icon: 'Image',
    fields: [
      { key: 'remove_current_images', label: 'Remove current product images', type: 'checkbox' },
      { key: 'import_only_without_images', label: 'Import just for products without images', type: 'checkbox' },
      { key: 'image_url_1', label: 'Images urls #1', type: 'column' },
      { key: 'image_alt_1', label: 'Images alt text #1', type: 'column' },
      { key: 'media_content_type_1', label: 'Media content type #1', type: 'fixed', options: ['Image', 'Video', 'External video', '3D model'] },
    ],
  },
  {
    key: 'publishing',
    label: 'Publishing',
    icon: 'Megaphone',
    fields: [
      { key: 'include_sales_channels', label: 'Include to sales channels', type: 'tags', options: SALES_CHANNELS },
      { key: 'exclude_sales_channels', label: 'Exclude from sales channels', type: 'tags', options: SALES_CHANNELS },
      { key: 'include_markets', label: 'Include to markets', type: 'tags' },
      { key: 'exclude_markets', label: 'Exclude from markets', type: 'tags' },
      { key: 'include_shipping_profiles', label: 'Include to shipping profiles', type: 'tags' },
      { key: 'exclude_shipping_profiles', label: 'Exclude from shipping profiles', type: 'tags' },
    ],
  },
  {
    key: 'pricing',
    label: 'Pricing',
    icon: 'Tag',
    fields: [
      { key: 'price', label: 'Price', type: 'column', group: 'Product pricing' },
      { key: 'compare_at_price', label: 'Compare at price', type: 'column', group: 'Product pricing' },
      { key: 'cost_per_item', label: 'Cost per item', type: 'column', group: 'Product pricing' },
      { key: 'total_measurement', label: 'Total measurement', type: 'column', group: 'Unit pricing' },
      { key: 'unit_total_measurement', label: 'Unit for total measurement', type: 'column', group: 'Unit pricing' },
      { key: 'base_measurement', label: 'Base measurement', type: 'column', group: 'Unit pricing' },
      { key: 'unit_base_measurement', label: 'Unit for base measurement', type: 'column', group: 'Unit pricing' },
      { key: 'charge_tax', label: 'Charge tax on this product', type: 'fixed', options: ['Yes', 'No'], group: 'Taxation' },
    ],
  },
  {
    key: 'inventory',
    label: 'Inventory',
    icon: 'Boxes',
    fields: [
      { key: 'sku', label: 'SKU (Stock Keeping Unit)', type: 'column' },
      { key: 'barcode', label: 'Barcode (ISBN, UPC, GTIN, etc.)', type: 'column' },
      { key: 'continue_selling', label: 'Continue selling when out of stock', type: 'fixed', options: ['No', 'Yes'] },
      { key: 'quantity_import_method', label: 'Quantity Import Method', type: 'fixed', options: ['Set', 'Add', 'Subtract'] },
      { key: 'quantity_location', label: 'Quantity Location', type: 'column' },
      { key: 'quantity', label: 'Quantity', type: 'column' },
    ],
  },
  {
    key: 'shipping',
    label: 'Shipping',
    icon: 'Truck',
    fields: [
      { key: 'weight', label: 'Weight', type: 'column' },
      { key: 'weight_unit', label: 'Weight unit', type: 'fixed', options: ['kg', 'g', 'lb', 'oz'] },
      { key: 'requires_shipping', label: 'This is a physical product', type: 'fixed', options: ['Yes', 'No'] },
      { key: 'hs_code', label: 'Harmonized System (HS) code', type: 'column' },
      { key: 'country_of_origin', label: 'Country / Region of origin', type: 'column' },
    ],
  },
  {
    key: 'variants',
    label: 'Variants',
    icon: 'Layers',
    fields: [
      { key: 'option1_name', label: 'Option 1 name', type: 'column' },
      { key: 'option1_value', label: 'Option 1 value', type: 'column' },
      { key: 'option2_name', label: 'Option 2 name', type: 'column' },
      { key: 'option2_value', label: 'Option 2 value', type: 'column' },
      { key: 'option3_name', label: 'Option 3 name', type: 'column' },
      { key: 'option3_value', label: 'Option 3 value', type: 'column' },
    ],
  },
  {
    key: 'se_listing',
    label: 'SE listing preview',
    icon: 'Search',
    fields: [
      { key: 'seo_title', label: 'Page title (SEO)', type: 'column' },
      { key: 'seo_description', label: 'Meta description (SEO)', type: 'column' },
      { key: 'url_handle', label: 'URL handle', type: 'column' },
    ],
  },
  {
    key: 'associations',
    label: 'Associations',
    icon: 'Network',
    fields: [
      { key: 'product_type', label: 'Product type', type: 'column' },
      { key: 'vendor', label: 'Vendor', type: 'column' },
      { key: 'collections', label: 'Collections', type: 'column' },
    ],
  },
  {
    key: 'product_meta_fields',
    label: 'Product meta fields',
    icon: 'Database',
    fields: [
      { key: 'metafield_1_key', label: 'Metafield #1 key', type: 'column' },
      { key: 'metafield_1_value', label: 'Metafield #1 value', type: 'column' },
    ],
  },
  { key: 'custom_fields', label: 'Custom fields', icon: 'Settings2', badge: 'Extra', locked: true },
  { key: 'import_conditions', label: 'Import conditions', icon: 'ListChecks', badge: 'Extra', locked: true },
  { key: 'icecat', label: 'Icecat', icon: 'Package', badge: 'Extra', locked: true },
  { key: 'import_optimization', label: 'Import Optimization', icon: 'Wand2', badge: 'New', locked: true },
  { key: 'chatgpt_assistant', label: 'ChatGPT assistant', icon: 'Bot', badge: 'New', locked: true },
]
