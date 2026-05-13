<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change ENUM columns to VARCHAR to allow flexible link types
        // and entity types (gap, artifact_file, requirement, etc.)
        DB::statement("ALTER TABLE ai_agent_traceability_links
            MODIFY source_type VARCHAR(50) NOT NULL,
            MODIFY target_type VARCHAR(50) NOT NULL,
            MODIFY link_type   VARCHAR(50) NOT NULL DEFAULT 'implements'
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ai_agent_traceability_links
            MODIFY source_type ENUM('requirement','screen','component','api_endpoint','code_file','artifact') NOT NULL,
            MODIFY target_type ENUM('requirement','screen','component','api_endpoint','code_file','artifact') NOT NULL,
            MODIFY link_type   ENUM('implements','designs','tests','documents','depends_on') NOT NULL DEFAULT 'implements'
        ");
    }
};
