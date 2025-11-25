# Backward Compatibility Analysis

## Migration Changes

### Original Migration (v1.0 / HEAD~1)
- `id` (uuid)
- `model` (string)
- `model_id` (integer, nullable) - **Never used in code**
- `parent_staging_id` (uuid, nullable)
- `data` (json)
- `timestamps`
- `expires_at` (timestamp, nullable) - **Never used in code**

### Current Migration (After cleanup)
- `id` (uuid)
- `model` (string)
- `model_id` (integer, nullable) - **Kept for backward compatibility, marked as "Reserved for future use"**
- `parent_staging_id` (uuid, nullable)
- `parent_model` (string, nullable) - **NEW: Required by hasMany relationships**
- `relationship_type` (string, nullable) - **NEW: Required by hasMany relationships**
- `sort_order` (integer, default 0) - **NEW: Required for sorting staged items**
- `data` (json)
- `timestamps`
- `expires_at` (timestamp, nullable) - **Kept for backward compatibility, marked as "Reserved for future use"**
- Index on `[parent_staging_id, parent_model]` - **NEW: For performance**

## Backward Compatibility Status

### ✅ Maintained
- `model_id` - Kept in migration (was in original, never used but maintained)
- `expires_at` - Kept in migration (was in original, never used but maintained)
- All original columns are preserved

### ⚠️ Breaking Changes (New Columns Required)
- `parent_model` - **REQUIRED** for hasMany relationships
- `relationship_type` - **REQUIRED** for hasMany relationships
- `sort_order` - **REQUIRED** for sorted staging

**Impact**: Users who installed v1.0 and ran the original migration will need to add these columns when updating to the version that includes hasMany relationship support.

### Migration Required for Existing Installations

If you have an existing installation with the old schema, create a new migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('staging_entries', function (Blueprint $table) {
            $table->string('parent_model')->nullable()->after('parent_staging_id');
            $table->string('relationship_type')->nullable()->after('parent_model');
            $table->integer('sort_order')->default(0)->after('relationship_type');
            
            $table->index(['parent_staging_id', 'parent_model']);
        });
    }

    public function down(): void
    {
        Schema::table('staging_entries', function (Blueprint $table) {
            $table->dropIndex(['parent_staging_id', 'parent_model']);
            $table->dropColumn(['parent_model', 'relationship_type', 'sort_order']);
        });
    }
};
```

## Summary

- ✅ **Unused columns preserved** (`model_id`, `expires_at`) for backward compatibility
- ✅ **New columns added** to migration stub for new installations
- ⚠️ **Existing installations** need to run a migration to add new columns
- ✅ **Code uses all columns** correctly - no unused features

