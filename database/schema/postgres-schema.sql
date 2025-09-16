DROP TABLE IF EXISTS activity_log;
CREATE TABLE activity_log (
  id BIGSERIAL NOT NULL
  log_name varchar(255) DEFAULT NULL
  description text NOT NULL
  subject_type varchar(255) DEFAULT NULL
  subject_id varchar(255) DEFAULT NULL
  event varchar(255) DEFAULT NULL
  causer_type varchar(255) DEFAULT NULL
  causer_id varchar(255) DEFAULT NULL
  properties json DEFAULT NULL
  batch_uuid varchar(255) DEFAULT NULL
  ip_address varchar(45) DEFAULT NULL
  user_agent text
  request_method varchar(10) DEFAULT NULL
  request_url text
  response_status int DEFAULT NULL
  execution_time_ms decimal(10,2) DEFAULT NULL
  memory_usage_mb decimal(10,2) DEFAULT NULL
  query_count int DEFAULT NULL
  context json DEFAULT NULL
  tags json DEFAULT NULL
  severity_level VARCHAR(50) NOT NULL DEFAULT 'info'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS activity_log_api_requests;
CREATE TABLE activity_log_api_requests (
  id BIGSERIAL NOT NULL
  activity_log_id bigint DEFAULT NULL
  endpoint varchar(500) NOT NULL
  method varchar(10) NOT NULL
  api_version varchar(20) DEFAULT NULL
  client_id varchar(100) DEFAULT NULL
  api_key_id varchar(100) DEFAULT NULL
  request_headers json DEFAULT NULL
  request_body json DEFAULT NULL
  query_parameters json DEFAULT NULL
  response_status int NOT NULL
  response_headers json DEFAULT NULL
  response_body json DEFAULT NULL
  response_time_ms decimal(10,2) NOT NULL
  response_size_bytes int DEFAULT NULL
  ip_address varchar(45) NOT NULL
  user_agent text
  is_authenticated int NOT NULL DEFAULT '0'
  user_id bigint DEFAULT NULL
  error_message text
  rate_limit_info json DEFAULT NULL
);
DROP TABLE IF EXISTS activity_log_background_jobs;
CREATE TABLE activity_log_background_jobs (
  id BIGSERIAL NOT NULL
  activity_log_id bigint DEFAULT NULL
  job_id varchar(100) DEFAULT NULL
  job_class varchar(500) NOT NULL
  queue_name varchar(100) DEFAULT NULL
  status VARCHAR(50) NOT NULL DEFAULT 'pending'
  payload json DEFAULT NULL
  attempts int NOT NULL DEFAULT '0'
  max_attempts int DEFAULT NULL
  queued_at timestamp NOT NULL
  started_at timestamp NULL DEFAULT NULL
  completed_at timestamp NULL DEFAULT NULL
  failed_at timestamp NULL DEFAULT NULL
  execution_time_seconds decimal(10,2) DEFAULT NULL
  memory_peak_mb decimal(10,2) DEFAULT NULL
  exception_message text
  exception_trace text
  user_id bigint DEFAULT NULL
  tags json DEFAULT NULL
);
DROP TABLE IF EXISTS activity_log_bulk_operations;
CREATE TABLE activity_log_bulk_operations (
  id BIGSERIAL NOT NULL
  batch_uuid varchar(255) NOT NULL
  operation_type varchar(100) NOT NULL
  model_type varchar(255) NOT NULL
  total_records int NOT NULL
  processed_records int NOT NULL DEFAULT '0'
  failed_records int NOT NULL DEFAULT '0'
  status VARCHAR(50) NOT NULL DEFAULT 'pending'
  parameters json DEFAULT NULL
  results json DEFAULT NULL
  error_message text
  initiated_by bigint DEFAULT NULL
  started_at timestamp NULL DEFAULT NULL
  completed_at timestamp NULL DEFAULT NULL
  execution_time_seconds decimal(10,2) DEFAULT NULL
  memory_peak_mb decimal(10,2) DEFAULT NULL
);
DROP TABLE IF EXISTS activity_log_queries;
CREATE TABLE activity_log_queries (
  id BIGSERIAL NOT NULL
  activity_log_id bigint NOT NULL
  sql text NOT NULL
  bindings json DEFAULT NULL
  execution_time_ms decimal(10,2) NOT NULL
  connection_name varchar(100) DEFAULT NULL
  query_type varchar(255) NOT NULL DEFAULT 'other'
  table_name varchar(100) DEFAULT NULL
  rows_affected int DEFAULT NULL
);
DROP TABLE IF EXISTS activity_log_statistics;
CREATE TABLE activity_log_statistics (
  id BIGSERIAL NOT NULL
  date date NOT NULL
  period_type varchar(20) NOT NULL
  log_name varchar(100) DEFAULT NULL
  event_type varchar(100) DEFAULT NULL
  model_type varchar(255) DEFAULT NULL
  user_id bigint DEFAULT NULL
  total_activities int NOT NULL DEFAULT '0'
  unique_users int NOT NULL DEFAULT '0'
  unique_ips int NOT NULL DEFAULT '0'
  activity_breakdown json DEFAULT NULL
  hourly_distribution json DEFAULT NULL
  top_users json DEFAULT NULL
  top_actions json DEFAULT NULL
  top_models json DEFAULT NULL
  avg_execution_time_ms decimal(10,2) DEFAULT NULL
  max_execution_time_ms decimal(10,2) DEFAULT NULL
  total_execution_time_ms decimal(15,2) DEFAULT NULL
  avg_memory_usage_mb decimal(10,2) DEFAULT NULL
  max_memory_usage_mb decimal(10,2) DEFAULT NULL
  total_queries int NOT NULL DEFAULT '0'
  error_count int NOT NULL DEFAULT '0'
  warning_count int NOT NULL DEFAULT '0'
  severity_breakdown json DEFAULT NULL
  response_status_breakdown json DEFAULT NULL
  browser_breakdown json DEFAULT NULL
  os_breakdown json DEFAULT NULL
  device_breakdown json DEFAULT NULL
);
DROP TABLE IF EXISTS cache;
CREATE TABLE cache (
  key varchar(255) NOT NULL
  value TEXT NOT NULL
  expiration int NOT NULL
);
DROP TABLE IF EXISTS cache_locks;
CREATE TABLE cache_locks (
  key varchar(255) NOT NULL
  owner varchar(255) NOT NULL
  expiration int NOT NULL
);
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS consumable_transactions;
CREATE TABLE consumable_transactions (
  id BIGSERIAL NOT NULL
  consumable_id bigint NOT NULL
  type varchar(255) NOT NULL
  quantity decimal(10,3) NOT NULL
  balance_after decimal(10,3) NOT NULL
  user_id bigint DEFAULT NULL
  reference_type varchar(255) DEFAULT NULL
  reference_id bigint DEFAULT NULL
  notes text
  metadata json DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS consumable_types;
CREATE TABLE consumable_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS consumable_units;
CREATE TABLE consumable_units (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  symbol varchar(20) DEFAULT NULL
  description text
  category varchar(50) NOT NULL
  conversion_factor decimal(20,10) DEFAULT NULL
  base_unit varchar(50) DEFAULT NULL
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS consumables;
CREATE TABLE consumables (
  id BIGSERIAL NOT NULL
  name varchar(255) DEFAULT NULL
  consumable_type_id bigint DEFAULT NULL
  consumable_unit_id bigint DEFAULT NULL
  supplier_id bigint DEFAULT NULL
  packaging_type_id bigint DEFAULT NULL
  master_seed_catalog_id bigint DEFAULT NULL
  master_cultivar_id bigint DEFAULT NULL
  cultivar varchar(255) DEFAULT NULL
  initial_stock decimal(10,3) NOT NULL DEFAULT '0.000'
  current_stock decimal(10,2) NOT NULL DEFAULT '0.00'
  units_quantity decimal(10,2) NOT NULL DEFAULT '1.00'
  restock_threshold decimal(10,2) NOT NULL DEFAULT '0.00'
  restock_quantity decimal(10,2) NOT NULL DEFAULT '0.00'
  cost_per_unit decimal(10,2) DEFAULT NULL
  quantity_per_unit decimal(10,2) NOT NULL DEFAULT '1.00'
  quantity_unit varchar(255) DEFAULT NULL
  total_quantity decimal(10,2) NOT NULL DEFAULT '0.00'
  consumed_quantity decimal(10,2) NOT NULL DEFAULT '0.00'
  notes text
  lot_no varchar(255) DEFAULT NULL
  is_active int NOT NULL DEFAULT '1'
  last_ordered_at timestamp NULL DEFAULT NULL
  deleted_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_alerts;
CREATE TABLE crop_alerts (
  id BIGSERIAL NOT NULL
  alert_type varchar(255) NOT NULL
  conditions json NOT NULL
  is_active int NOT NULL DEFAULT '1'
  last_executed_at timestamp NULL DEFAULT NULL
  scheduled_for timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_batches;
CREATE TABLE crop_batches (
  id BIGSERIAL NOT NULL
  recipe_id bigint NOT NULL
  order_id bigint DEFAULT NULL
  crop_plan_id bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_batches_list_view;
DROP TABLE IF EXISTS crop_harvest;
CREATE TABLE crop_harvest (
  id BIGSERIAL NOT NULL
  crop_id bigint NOT NULL
  harvest_id bigint NOT NULL
  harvested_weight_grams decimal(8,2) NOT NULL
  percentage_harvested decimal(5,2) NOT NULL DEFAULT '100.00'
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_plan_statuses;
CREATE TABLE crop_plan_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_plans;
CREATE TABLE crop_plans (
  id BIGSERIAL NOT NULL
  aggregated_crop_plan_id bigint DEFAULT NULL
  notes text
  status_id bigint NOT NULL
  created_by bigint NOT NULL
  order_id bigint DEFAULT NULL
  recipe_id bigint DEFAULT NULL
  variety_id bigint DEFAULT NULL
  trays_needed int NOT NULL DEFAULT '0'
  grams_needed decimal(8,2) NOT NULL DEFAULT '0.00'
  grams_per_tray decimal(8,2) NOT NULL DEFAULT '0.00'
  plant_by_date date DEFAULT NULL
  seed_soak_date date DEFAULT NULL
  expected_harvest_date date DEFAULT NULL
  delivery_date date DEFAULT NULL
  calculation_details json DEFAULT NULL
  order_items_included json DEFAULT NULL
  approved_by bigint DEFAULT NULL
  approved_at timestamp NULL DEFAULT NULL
  admin_notes text
  is_missing_recipe int NOT NULL DEFAULT '0'
  missing_recipe_notes varchar(255) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_plans_aggregate;
CREATE TABLE crop_plans_aggregate (
  id BIGSERIAL NOT NULL
  variety_id bigint NOT NULL
  harvest_date date NOT NULL
  total_grams_needed decimal(10,2) NOT NULL
  total_trays_needed int NOT NULL
  grams_per_tray decimal(8,2) NOT NULL
  plant_date date NOT NULL
  seed_soak_date date DEFAULT NULL
  status VARCHAR(50) NOT NULL DEFAULT 'draft'
  calculation_details json DEFAULT NULL
  created_by bigint NOT NULL
  updated_by bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_stage_history;
CREATE TABLE crop_stage_history (
  id BIGSERIAL NOT NULL
  crop_id bigint NOT NULL
  crop_batch_id bigint NOT NULL
  stage_id bigint NOT NULL
  entered_at timestamp NOT NULL
  exited_at timestamp NULL DEFAULT NULL
  notes text
  created_by bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_stages;
CREATE TABLE crop_stages (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  typical_duration_days int DEFAULT NULL
  requires_light int NOT NULL DEFAULT '0'
  requires_watering int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crop_statuses;
CREATE TABLE crop_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  is_final int NOT NULL DEFAULT '0'
  allows_modifications int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS crops;
CREATE TABLE crops (
  id BIGSERIAL NOT NULL
  crop_batch_id bigint DEFAULT NULL
  recipe_id bigint NOT NULL
  order_id bigint DEFAULT NULL
  crop_plan_id bigint DEFAULT NULL
  tray_number varchar(255) NOT NULL
  current_stage_id bigint NOT NULL
  stage_updated_at timestamp NULL DEFAULT NULL
  soaking_at timestamp NULL DEFAULT NULL
  requires_soaking BOOLEAN NOT NULL DEFAULT '0'
  germination_at timestamp NULL DEFAULT NULL
  blackout_at timestamp NULL DEFAULT NULL
  light_at timestamp NULL DEFAULT NULL
  watering_suspended_at timestamp NULL DEFAULT NULL
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS currencies;
CREATE TABLE currencies (
  id BIGSERIAL NOT NULL
  code varchar(3) NOT NULL
  name varchar(100) NOT NULL
  symbol varchar(10) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS customer_types;
CREATE TABLE customer_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id BIGSERIAL NOT NULL
  contact_name varchar(255) NOT NULL
  email varchar(255) NOT NULL
  cc_email varchar(255) DEFAULT NULL
  phone varchar(255) DEFAULT NULL
  customer_type_id bigint NOT NULL
  business_name varchar(255) DEFAULT NULL
  wholesale_discount_percentage decimal(5,2) NOT NULL DEFAULT '0.00'
  address text
  city varchar(255) DEFAULT NULL
  province varchar(255) DEFAULT NULL
  postal_code varchar(20) DEFAULT NULL
  country varchar(2) NOT NULL DEFAULT 'CA'
  user_id bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS delivery_statuses;
CREATE TABLE delivery_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS fulfillment_statuses;
CREATE TABLE fulfillment_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  is_final int NOT NULL DEFAULT '0'
  allows_modifications int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS harvests;
CREATE TABLE harvests (
  id BIGSERIAL NOT NULL
  master_cultivar_id bigint NOT NULL
  user_id bigint NOT NULL
  total_weight_grams decimal(10,2) NOT NULL
  tray_count decimal(8,2) NOT NULL
  harvest_date date NOT NULL
  week_start_date date DEFAULT NULL
  notes text
  average_weight_per_tray decimal(10,2) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS inventory_reservation_statuses;
CREATE TABLE inventory_reservation_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  is_final int NOT NULL DEFAULT '0'
  allows_modifications int NOT NULL DEFAULT '1'
  auto_release_hours int DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS inventory_reservations;
CREATE TABLE inventory_reservations (
  id BIGSERIAL NOT NULL
  product_inventory_id bigint NOT NULL
  product_id bigint NOT NULL
  order_id bigint NOT NULL
  order_item_id bigint NOT NULL
  quantity decimal(10,2) NOT NULL
  status_id bigint NOT NULL
  expires_at timestamp NULL DEFAULT NULL
  fulfilled_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS inventory_transaction_types;
CREATE TABLE inventory_transaction_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS inventory_transactions;
CREATE TABLE inventory_transactions (
  id BIGSERIAL NOT NULL
  product_inventory_id bigint DEFAULT NULL
  product_id bigint NOT NULL
  inventory_transaction_type_id bigint NOT NULL
  quantity decimal(10,2) NOT NULL
  balance_after decimal(10,2) NOT NULL
  unit_cost decimal(10,2) DEFAULT NULL
  total_cost decimal(10,2) DEFAULT NULL
  reference_type varchar(255) DEFAULT NULL
  reference_id bigint DEFAULT NULL
  user_id bigint NOT NULL
  notes text
  metadata json DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS invoice_statuses;
CREATE TABLE invoice_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
  id BIGSERIAL NOT NULL
  order_id bigint DEFAULT NULL
  amount decimal(10,2) NOT NULL
  payment_status_id bigint DEFAULT NULL
  sent_at timestamp NULL DEFAULT NULL
  paid_at timestamp NULL DEFAULT NULL
  due_date date DEFAULT NULL
  invoice_number varchar(255) NOT NULL
  notes text
  is_consolidated int NOT NULL DEFAULT '0'
  consolidated_order_count int NOT NULL DEFAULT '1'
  consolidated_order_ids json DEFAULT NULL
  customer_id bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS job_batches;
CREATE TABLE job_batches (
  id varchar(255) NOT NULL
  name varchar(255) NOT NULL
  total_jobs int NOT NULL
  pending_jobs int NOT NULL
  failed_jobs int NOT NULL
  failed_job_ids text NOT NULL
  options text
  cancelled_at int DEFAULT NULL
  finished_at int DEFAULT NULL
);
DROP TABLE IF EXISTS jobs;
CREATE TABLE jobs (
  id BIGSERIAL NOT NULL
  queue varchar(255) NOT NULL
  payload text NOT NULL
  attempts int NOT NULL
  reserved_at int DEFAULT NULL
  available_at int NOT NULL
  created_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS master_cultivars;
CREATE TABLE master_cultivars (
  id BIGSERIAL NOT NULL
  master_seed_catalog_id bigint NOT NULL
  cultivar_name varchar(255) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  aliases json DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS master_seed_catalog;
CREATE TABLE master_seed_catalog (
  id BIGSERIAL NOT NULL
  common_name varchar(255) NOT NULL
  cultivar_id bigint DEFAULT NULL
  category varchar(255) DEFAULT NULL
  aliases json DEFAULT NULL
  growing_notes text
  description text
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
  cultivars json DEFAULT NULL
);
DROP TABLE IF EXISTS migrations;
CREATE TABLE migrations (
  id SERIAL NOT NULL
  migration varchar(255) NOT NULL
  batch int NOT NULL
);
DROP TABLE IF EXISTS model_has_permissions;
CREATE TABLE model_has_permissions (
  permission_id bigint NOT NULL
  model_type varchar(255) NOT NULL
  model_id bigint NOT NULL
);
DROP TABLE IF EXISTS model_has_roles;
CREATE TABLE model_has_roles (
  role_id bigint NOT NULL
  model_type varchar(255) NOT NULL
  model_id bigint NOT NULL
);
DROP TABLE IF EXISTS notification_settings;
CREATE TABLE notification_settings (
  id BIGSERIAL NOT NULL
  user_id bigint NOT NULL
  channel VARCHAR(50) NOT NULL
  type varchar(255) NOT NULL
  is_enabled int NOT NULL DEFAULT '1'
  settings json DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
  id varchar(255) NOT NULL
  type varchar(255) NOT NULL
  notifiable_type varchar(255) NOT NULL
  notifiable_id bigint NOT NULL
  data text NOT NULL
  read_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS order_classifications;
CREATE TABLE order_classifications (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS order_packagings;
CREATE TABLE order_packagings (
  id BIGSERIAL NOT NULL
  order_id bigint NOT NULL
  packaging_type_id bigint NOT NULL
  quantity int NOT NULL
  price_per_unit decimal(10,2) NOT NULL
  total_price decimal(10,2) NOT NULL
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS order_products;
CREATE TABLE order_products (
  id BIGSERIAL NOT NULL
  order_id bigint NOT NULL
  product_id bigint NOT NULL
  price_variation_id bigint DEFAULT NULL
  quantity decimal(10,3) NOT NULL DEFAULT '0.000'
  quantity_unit varchar(20) NOT NULL DEFAULT 'units'
  quantity_in_grams decimal(10,3) DEFAULT NULL
  price decimal(10,2) NOT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS order_statuses;
CREATE TABLE order_statuses (
  id BIGSERIAL NOT NULL
  code varchar(255) NOT NULL
  name varchar(255) NOT NULL
  description text
  color varchar(255) NOT NULL
  badge_color varchar(255) DEFAULT NULL
  stage VARCHAR(50) NOT NULL
  requires_crops int NOT NULL DEFAULT '0'
  is_active int NOT NULL DEFAULT '1'
  is_final int NOT NULL DEFAULT '0'
  allows_modifications int NOT NULL DEFAULT '1'
  sort_order int NOT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS order_types;
CREATE TABLE order_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id BIGSERIAL NOT NULL
  user_id bigint NOT NULL
  customer_id bigint DEFAULT NULL
  harvest_date date DEFAULT NULL
  delivery_date date DEFAULT NULL
  harvest_day VARCHAR(50) DEFAULT NULL
  delivery_day VARCHAR(50) DEFAULT NULL
  start_delay_weeks int NOT NULL DEFAULT '2'
  status_id bigint DEFAULT NULL
  crop_status_id bigint DEFAULT NULL
  fulfillment_status_id bigint DEFAULT NULL
  payment_status_id bigint DEFAULT NULL
  delivery_status_id bigint DEFAULT NULL
  customer_type VARCHAR(50) NOT NULL DEFAULT 'b2b'
  order_type_id bigint DEFAULT NULL
  billing_frequency varchar(255) DEFAULT NULL
  requires_invoice int NOT NULL DEFAULT '0'
  billing_period_start date DEFAULT NULL
  billing_period_end date DEFAULT NULL
  consolidated_invoice_id bigint DEFAULT NULL
  billing_preferences json DEFAULT NULL
  order_classification_id bigint DEFAULT NULL
  billing_period varchar(255) DEFAULT NULL
  is_recurring int NOT NULL DEFAULT '0'
  parent_recurring_order_id bigint DEFAULT NULL
  recurring_frequency varchar(255) DEFAULT NULL
  recurring_start_date date DEFAULT NULL
  recurring_end_date date DEFAULT NULL
  is_recurring_active int NOT NULL DEFAULT '1'
  recurring_days_of_week json DEFAULT NULL
  recurring_interval int DEFAULT NULL
  last_generated_at TIMESTAMP DEFAULT NULL
  next_generation_date TIMESTAMP DEFAULT NULL
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS packaging_type_categories;
CREATE TABLE packaging_type_categories (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS packaging_types;
CREATE TABLE packaging_types (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  type_category_id bigint NOT NULL
  unit_type_id bigint NOT NULL
  capacity_volume decimal(10,2) DEFAULT NULL
  volume_unit varchar(20) DEFAULT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  cost_per_unit decimal(10,2) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS packaging_unit_types;
CREATE TABLE packaging_unit_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS password_reset_tokens;
CREATE TABLE password_reset_tokens (
  email varchar(255) NOT NULL
  token varchar(255) NOT NULL
);
DROP TABLE IF EXISTS payment_methods;
CREATE TABLE payment_methods (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  requires_processing int NOT NULL DEFAULT '0'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS payment_statuses;
CREATE TABLE payment_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  is_final int NOT NULL DEFAULT '0'
  allows_modifications int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
  id BIGSERIAL NOT NULL
  order_id bigint NOT NULL
  amount decimal(10,2) NOT NULL
  payment_method_id bigint DEFAULT NULL
  status_id bigint NOT NULL
  transaction_id varchar(255) DEFAULT NULL
  paid_at timestamp NULL DEFAULT NULL
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  guard_name varchar(255) NOT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS personal_access_tokens;
CREATE TABLE personal_access_tokens (
  id BIGSERIAL NOT NULL
  tokenable_type varchar(255) NOT NULL
  tokenable_id bigint NOT NULL
  name varchar(255) NOT NULL
  token varchar(64) NOT NULL
  abilities text
  last_used_at timestamp NULL DEFAULT NULL
  expires_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_inventories;
CREATE TABLE product_inventories (
  id BIGSERIAL NOT NULL
  product_id bigint NOT NULL
  price_variation_id bigint NOT NULL
  batch_number varchar(255) DEFAULT NULL
  lot_number varchar(255) DEFAULT NULL
  quantity decimal(10,2) NOT NULL
  reserved_quantity decimal(10,2) NOT NULL DEFAULT '0.00'
  available_quantity decimal(10,2) DEFAULT NULL
  cost_per_unit decimal(10,2) DEFAULT NULL
  expiration_date date DEFAULT NULL
  production_date date DEFAULT NULL
  location varchar(255) DEFAULT NULL
  notes text
  product_inventory_status_id bigint NOT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_inventory_statuses;
CREATE TABLE product_inventory_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_inventory_summary;
DROP TABLE IF EXISTS product_mix_components;
CREATE TABLE product_mix_components (
  id BIGSERIAL NOT NULL
  product_mix_id bigint NOT NULL
  master_seed_catalog_id bigint DEFAULT NULL
  percentage decimal(8,5) NOT NULL
  cultivar varchar(255) DEFAULT NULL
  recipe_id bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_mixes;
CREATE TABLE product_mixes (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_photos;
CREATE TABLE product_photos (
  id BIGSERIAL NOT NULL
  product_id bigint NOT NULL
  photo varchar(255) NOT NULL
  is_default int NOT NULL DEFAULT '0'
  "order" int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_price_variations;
CREATE TABLE product_price_variations (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  is_name_manual int NOT NULL DEFAULT '0'
  unit varchar(255) NOT NULL DEFAULT 'units'
  pricing_unit varchar(255) DEFAULT NULL
  sku varchar(255) DEFAULT NULL
  weight decimal(10,2) DEFAULT NULL
  price decimal(10,2) NOT NULL
  packaging_type_id bigint DEFAULT NULL
  pricing_type varchar(255) NOT NULL DEFAULT 'retail'
  fill_weight_grams decimal(8,2) DEFAULT NULL
  template_id bigint DEFAULT NULL
  is_default int NOT NULL DEFAULT '0'
  is_global int NOT NULL DEFAULT '0'
  is_active int NOT NULL DEFAULT '1'
  product_id bigint DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS product_stock_statuses;
CREATE TABLE product_stock_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  description text
  sku varchar(255) DEFAULT NULL
  master_seed_catalog_id bigint DEFAULT NULL
  base_price decimal(10,2) DEFAULT NULL
  wholesale_price decimal(10,2) DEFAULT NULL
  bulk_price decimal(10,2) DEFAULT NULL
  special_price decimal(10,2) DEFAULT NULL
  wholesale_discount_percentage decimal(5,2) NOT NULL DEFAULT '15.00'
  is_visible_in_store int NOT NULL DEFAULT '1'
  active int NOT NULL DEFAULT '1'
  image varchar(255) DEFAULT NULL
  category_id bigint DEFAULT NULL
  product_mix_id bigint DEFAULT NULL
  recipe_id bigint DEFAULT NULL
  total_stock decimal(10,2) NOT NULL DEFAULT '0.00'
  reserved_stock decimal(10,2) NOT NULL DEFAULT '0.00'
  reorder_threshold decimal(10,2) NOT NULL DEFAULT '0.00'
  track_inventory int NOT NULL DEFAULT '1'
  stock_status_id bigint NOT NULL DEFAULT '1'
  deleted_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS recipe_stage_types;
CREATE TABLE recipe_stage_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS recipe_watering_schedule;
CREATE TABLE recipe_watering_schedule (
  id BIGSERIAL NOT NULL
  recipe_id bigint NOT NULL
  day_number int NOT NULL
  water_amount_ml decimal(8,2) NOT NULL
  watering_method varchar(255) DEFAULT NULL
  needs_liquid_fertilizer int NOT NULL DEFAULT '0'
  notes text
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS recipes;
CREATE TABLE recipes (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  master_seed_catalog_id bigint DEFAULT NULL
  master_cultivar_id bigint DEFAULT NULL
  soil_consumable_id bigint DEFAULT NULL
  seed_consumable_id bigint DEFAULT NULL
  lot_number varchar(255) DEFAULT NULL
  lot_depleted_at timestamp NULL DEFAULT NULL
  seed_soak_hours decimal(5,2) NOT NULL DEFAULT '0.00'
  germination_days int NOT NULL
  blackout_days int NOT NULL
  light_days int NOT NULL
  days_to_maturity int DEFAULT NULL
  expected_yield_grams decimal(8,2) DEFAULT NULL
  seed_density_grams_per_tray decimal(8,2) DEFAULT NULL
  buffer_percentage decimal(5,2) NOT NULL DEFAULT '10.00'
  suspend_watering_hours int NOT NULL DEFAULT '0'
  is_active int NOT NULL DEFAULT '1'
  notes text
  suspend_water_hours int NOT NULL DEFAULT '24'
  common_name varchar(255) DEFAULT NULL
  cultivar_name varchar(255) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS recipes_optimized_view;
DROP TABLE IF EXISTS role_has_permissions;
CREATE TABLE role_has_permissions (
  permission_id bigint NOT NULL
  role_id bigint NOT NULL
);
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  guard_name varchar(255) NOT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS seed_categories;
CREATE TABLE seed_categories (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(50) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS seed_entries;
CREATE TABLE seed_entries (
  id BIGSERIAL NOT NULL
  common_name varchar(255) DEFAULT NULL
  cultivar_name varchar(255) DEFAULT NULL
  supplier_product_title varchar(255) NOT NULL
  supplier_id bigint NOT NULL
  supplier_sku varchar(255) DEFAULT NULL
  supplier_product_url varchar(255) NOT NULL
  image_url varchar(255) DEFAULT NULL
  description text
  tags json DEFAULT NULL
  url varchar(255) DEFAULT NULL
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS seed_price_history;
CREATE TABLE seed_price_history (
  id BIGSERIAL NOT NULL
  seed_variation_id bigint NOT NULL
  price decimal(10,2) NOT NULL
  currency varchar(3) NOT NULL DEFAULT 'USD'
  is_in_stock int NOT NULL DEFAULT '1'
  checked_at timestamp NOT NULL
  scraped_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS seed_scrape_uploads;
CREATE TABLE seed_scrape_uploads (
  id BIGSERIAL NOT NULL
  supplier_id bigint NOT NULL
  filename varchar(255) NOT NULL
  total_entries int NOT NULL DEFAULT '0'
  new_entries int NOT NULL DEFAULT '0'
  updated_entries int NOT NULL DEFAULT '0'
  failed_entries_count int NOT NULL DEFAULT '0'
  status varchar(255) NOT NULL DEFAULT 'pending'
  failed_entries json DEFAULT NULL
  uploaded_by bigint NOT NULL
  uploaded_at timestamp NOT NULL
  processed_at TIMESTAMP DEFAULT NULL
  notes text
  successful_entries int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS seed_variations;
CREATE TABLE seed_variations (
  id BIGSERIAL NOT NULL
  seed_entry_id bigint NOT NULL
  sku varchar(255) DEFAULT NULL
  consumable_id bigint DEFAULT NULL
  size varchar(255) NOT NULL
  weight_kg decimal(8,4) DEFAULT NULL
  original_weight_value decimal(8,4) DEFAULT NULL
  original_weight_unit varchar(255) DEFAULT NULL
  unit varchar(255) NOT NULL
  current_price decimal(10,2) DEFAULT NULL
  currency varchar(3) NOT NULL DEFAULT 'USD'
  is_available int NOT NULL DEFAULT '1'
  last_checked_at timestamp NULL DEFAULT NULL
  size_description varchar(255) DEFAULT NULL
  is_in_stock int DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS sessions;
CREATE TABLE sessions (
  id varchar(255) NOT NULL
  user_id bigint DEFAULT NULL
  ip_address varchar(45) DEFAULT NULL
  user_agent text
  payload TEXT NOT NULL
  last_activity int NOT NULL
);
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id BIGSERIAL NOT NULL
  key varchar(255) NOT NULL
  value text NOT NULL
  description text
  type varchar(255) NOT NULL DEFAULT 'text'
  "group" varchar(255) DEFAULT NULL
);
DROP TABLE IF EXISTS supplier_source_mappings;
CREATE TABLE supplier_source_mappings (
  id BIGSERIAL NOT NULL
  supplier_id bigint NOT NULL
  source_url varchar(255) DEFAULT NULL
  domain varchar(255) DEFAULT NULL
  source_name varchar(255) NOT NULL
  source_identifier varchar(255) NOT NULL
  mapping_data json DEFAULT NULL
  metadata json DEFAULT NULL
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS supplier_types;
CREATE TABLE supplier_types (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  supplier_type_id bigint NOT NULL
  contact_name varchar(255) DEFAULT NULL
  contact_email varchar(255) DEFAULT NULL
  contact_phone varchar(255) DEFAULT NULL
  address text
  notes text
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS task_schedules;
CREATE TABLE task_schedules (
  id BIGSERIAL NOT NULL
  resource_type varchar(255) DEFAULT NULL
  task_name varchar(255) DEFAULT NULL
  name varchar(255) NOT NULL
  description text
  frequency VARCHAR(50) NOT NULL
  schedule_config json NOT NULL
  time_of_day varchar(255) DEFAULT NULL
  day_of_week int DEFAULT NULL
  day_of_month int DEFAULT NULL
  conditions json DEFAULT NULL
  is_active int NOT NULL DEFAULT '1'
  last_run_at timestamp NULL DEFAULT NULL
  next_run_at timestamp NULL DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS task_types;
CREATE TABLE task_types (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  category varchar(255) DEFAULT NULL
  sort_order int NOT NULL DEFAULT '0'
  is_active int NOT NULL DEFAULT '1'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS tasks;
CREATE TABLE tasks (
  id BIGSERIAL NOT NULL
  title varchar(255) NOT NULL
  description text
  task_type VARCHAR(50) NOT NULL
  due_date date DEFAULT NULL
  completed_at timestamp NULL DEFAULT NULL
  assigned_to bigint DEFAULT NULL
  priority VARCHAR(50) NOT NULL DEFAULT 'medium'
  status VARCHAR(50) NOT NULL DEFAULT 'pending'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS time_card_statuses;
CREATE TABLE time_card_statuses (
  id BIGSERIAL NOT NULL
  code varchar(50) NOT NULL
  name varchar(100) NOT NULL
  description text
  color varchar(20) NOT NULL DEFAULT 'gray'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS time_card_tasks;
CREATE TABLE time_card_tasks (
  id BIGSERIAL NOT NULL
  time_card_id bigint NOT NULL
  task_name varchar(255) NOT NULL
  task_type_id bigint DEFAULT NULL
  is_custom int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS time_cards;
CREATE TABLE time_cards (
  id BIGSERIAL NOT NULL
  user_id bigint NOT NULL
  clock_in TIMESTAMP NOT NULL
  clock_out TIMESTAMP DEFAULT NULL
  duration_minutes int DEFAULT NULL
  work_date date NOT NULL
  time_card_status_id bigint NOT NULL
  max_shift_exceeded int NOT NULL DEFAULT '0'
  max_shift_exceeded_at TIMESTAMP DEFAULT NULL
  requires_review int NOT NULL DEFAULT '0'
  flags json DEFAULT NULL
  review_notes text
  notes text
  ip_address varchar(255) DEFAULT NULL
  user_agent varchar(255) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id BIGSERIAL NOT NULL
  name varchar(255) NOT NULL
  email varchar(255) NOT NULL
  phone varchar(255) DEFAULT NULL
  email_verified_at timestamp NULL DEFAULT NULL
  password varchar(255) DEFAULT NULL
  customer_type_id bigint DEFAULT NULL
  wholesale_discount_percentage decimal(5,2) NOT NULL DEFAULT '0.00'
  company_name varchar(255) DEFAULT NULL
  address text
  city varchar(255) DEFAULT NULL
  state varchar(255) DEFAULT NULL
  zip varchar(255) DEFAULT NULL
  remember_token varchar(100) DEFAULT NULL
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS volume_units;
CREATE TABLE volume_units (
  id BIGSERIAL NOT NULL
  code varchar(10) NOT NULL
  name varchar(50) NOT NULL
  symbol varchar(10) NOT NULL
  description text
  conversion_factor decimal(15,8) NOT NULL DEFAULT '1.00000000'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);
DROP TABLE IF EXISTS weight_units;
CREATE TABLE weight_units (
  id BIGSERIAL NOT NULL
  code varchar(10) NOT NULL
  name varchar(50) NOT NULL
  symbol varchar(10) NOT NULL
  description text
  conversion_factor decimal(15,8) NOT NULL DEFAULT '1.00000000'
  is_active int NOT NULL DEFAULT '1'
  sort_order int NOT NULL DEFAULT '0'
  created_at timestamp NULL DEFAULT NULL
  updated_at timestamp NULL DEFAULT NULL
);


