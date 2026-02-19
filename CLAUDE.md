# CLAUDE.md - Laravel Has Props

## Overview

`hanafalah/laravel-has-props` is a Laravel package that provides a flexible props/metadata system for Eloquent models. It enables storing dynamic, schema-less properties in a JSON column (`props`) while treating them as first-class model attributes.

**Key Features:**
- Virtual column support via `stancl/virtualcolumn` for transparent JSON attribute access
- Config props system for syncing properties between related models
- Current record tracking for models with version history
- Multi-tenant aware event handling

**Namespace:** `Hanafalah\LaravelHasProps`

**Dependencies:**
- `stancl/virtualcolumn` - Virtual column functionality
- `hanafalah/laravel-support` - Base Laravel support utilities

## Directory Structure

```
src/
├── Concerns/                    # Traits for models
│   ├── HasProps.php            # Main props functionality
│   ├── HasConfigProps.php      # Config-based prop syncing
│   ├── HasCurrent.php          # Current record tracking (timestamp-based)
│   ├── HasCurrentBackup.php    # Current record tracking (flag-based)
│   ├── CurrentChecking.php     # Simple current accessor
│   └── PropAttribute.php       # Custom columns helper
├── Events/
│   └── PropSubjectUpdated.php  # Event when prop subject changes
├── Jobs/
│   └── HandlePropSubject.php   # Queue job for prop sync
├── Listeners/
│   └── DispatchPropJobListener.php # Dispatches prop sync jobs
├── Models/
│   ├── ConfigProp.php          # Configuration prop model
│   └── Scopes/
│       └── HasCurrentScope.php # Global scope for current records
├── LaravelHasProps.php         # Package management class
└── LaravelHasPropsServiceProvider.php
```

## Key Traits

### HasProps

The primary trait for adding dynamic props to models. Uses `stancl/virtualcolumn` under the hood.

```php
use Hanafalah\LaravelHasProps\Concerns\HasProps;

class Patient extends Model
{
    use HasProps;

    protected $fillable = ['name', 'email'];
    // 'props' column automatically handled
}

// Usage - dynamic attributes stored in props JSON column
$patient = Patient::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'blood_type' => 'A+',        // Stored in props
    'allergies' => ['penicillin'] // Stored in props
]);

// Access like normal attributes
echo $patient->blood_type;  // 'A+'

// Query by props
Patient::whereProp('blood_type', 'A+')->get();
```

**Key Methods:**
- `getProps()` - Get decoded props array
- `setProps()` - Encode and set props
- `boolValidate($key)` - Validate boolean prop value
- `scopeProp($builder, $column)` - Add props column to select
- `scopeWhereProp($builder, $json_var, ...$args)` - Query JSON props

### HasConfigProps

Enables automatic syncing of props between reference and subject models via the `ConfigProp` model.

```php
use Hanafalah\LaravelHasProps\Concerns\HasConfigProps;

class Appointment extends Model
{
    use HasConfigProps;

    // Define which props to sync from related models
    protected $prop_attributes = [
        'patient' => PatientResource::class,  // Use resource for formatting
        'doctor' => ['name', 'specialty'],    // Or simple array of keys
    ];
}

// Sync props from patient to appointment
$appointment->sync($patient);

// Or listen for changes (creates ConfigProp record)
$appointment->listenProp($patient, ['name', 'phone']);

// Access synced props
$appointment->prop_patient; // Contains patient data
```

**Key Methods:**
- `sync($model, $attr)` - Sync props from a model
- `listenProp($model, $attr)` - Register to listen for prop changes
- `propResource($model, $resource, $excepts)` - Get formatted prop data
- `configFromReference()` - Morph relation to ConfigProp as reference
- `configFromSubject()` - Morph relation to ConfigProp as subject

### HasCurrent

Trait for tracking the "current" or latest version of a record using timestamps.

```php
use Hanafalah\LaravelHasProps\Concerns\HasCurrent;

class PatientRecord extends Model
{
    use HasCurrent;

    protected $fillable = ['patient_id', 'diagnosis', 'current'];

    // Define conditions for determining current record
    protected $current_conditions = ['patient_id'];
}

// Query current record
$current = PatientRecord::isCurrent([
    ['patient_id', $patientId]
])->first();

// Auto-set current timestamp on new records
$record->setCurrent($record);
```

### HasCurrentBackup

Alternative implementation using integer flags (1/0) instead of timestamps.

```php
use Hanafalah\LaravelHasProps\Concerns\HasCurrentBackup;

class InsurancePolicy extends Model
{
    use HasCurrentBackup;

    // When saving, previous current records are marked as not current
    // current = 1 (is current)
    // current = 0 (not current)
}
```

## Models

### ConfigProp

Stores configuration for prop syncing between models.

**Table:** `config_props`

**Columns:**
- `id` (ULID) - Primary key
- `reference_type` - Morph type of the reference model
- `reference_id` - ID of the reference model
- `subject_type` - Morph type of the subject model
- `subject_id` - ID of the subject model
- `props` (JSON) - Contains `list_config` and `current_data`

**Relationships:**
- `reference()` - Morph to reference model
- `subject()` - Morph to subject model

## Events and Jobs

### PropSubjectUpdated Event

Fired when a subject model with config props is updated.

```php
use Hanafalah\LaravelHasProps\Events\PropSubjectUpdated;

// Manually dispatch (usually handled by HasConfigProps trait)
event(new PropSubjectUpdated($model, $tenant));
```

### HandlePropSubject Job

Queued job for handling prop sync in multi-tenant environments.

- Queue: `SyncingProps`
- Handles tenant initialization before processing

## Usage Patterns

### Basic Props Storage

```php
// Model setup
class Product extends Model
{
    use HasProps;

    protected $fillable = ['name', 'price'];
}

// Store arbitrary data
$product = Product::create([
    'name' => 'Widget',
    'price' => 99.99,
    'specifications' => ['color' => 'blue', 'weight' => '1kg'],
    'metadata' => ['sku' => 'WDG-001']
]);

// Query by JSON path
Product::whereProp('specifications->color', 'blue')->get();
```

### Props Syncing Between Models

```php
// Parent model stores denormalized data from child
class Order extends Model
{
    use HasConfigProps;

    protected $prop_attributes = [
        'customer' => CustomerSummaryResource::class,
    ];
}

// When customer updates, order props update too
$order->sync($customer);
// $order->prop_customer now contains customer summary
```

### Current Record Pattern

```php
// Track latest version of a document
class Document extends Model
{
    use HasCurrent;

    protected $fillable = ['title', 'content', 'version', 'document_id', 'current'];
    protected $current_conditions = ['document_id'];
}

// Get current version
$currentDoc = Document::isCurrent([
    ['document_id', $documentId]
])->first();
```

## Configuration

Configuration is published to `config/laravel-has-props.php`:

```php
return [
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts'
    ],
    'database' => [
        'models' => [
            // Override model classes here
        ]
    ]
];
```

## Database Migration

The package includes a migration for the `config_props` table. Run migrations after installing:

```bash
php artisan migrate
```

## Multi-Tenant Considerations

When using with `hanafalah/microtenant`:

1. Events include tenant context for proper serialization
2. Jobs initialize tenant before processing
3. The `HasConfigProps` trait checks connection names to skip cross-tenant config updates

```php
// Events are tenant-aware
event(new PropSubjectUpdated($model, tenancy()->tenant));
```

## Common Pitfalls

1. **Props Column Missing** - Ensure your table has a `props` JSON column
2. **Fillable Conflict** - The `HasProps` trait auto-merges 'props' into fillable
3. **Query Performance** - JSON queries can be slow; consider indexing frequently queried props
4. **Circular Syncing** - Be careful with bidirectional prop syncing to avoid infinite loops
