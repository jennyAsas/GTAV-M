<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160)->unique();
            $table->string('location', 160)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 200);
            $table->string('type', 20);
            $table->text('description')->nullable();
            $table->string('venue', 200);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('registration_cutoff');
            $table->unsignedInteger('capacity');
            $table->decimal('ticket_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['chapter_id', 'starts_at']);
        });

        Schema::create('event_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment', 20); // speaker | gate_staff
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'assignment']);
            $table->index('user_id');
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_code', 20)->unique();
            $table->string('payment_status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'payment_status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_dates_check CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_capacity_check CHECK (capacity > 0)');
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_price_check CHECK (ticket_price >= 0)');
            DB::statement("ALTER TABLE events ADD CONSTRAINT events_type_check CHECK (type IN ('expo','summit','workshop'))");
            DB::statement("ALTER TABLE event_assignments ADD CONSTRAINT event_assignments_check CHECK (assignment IN ('speaker','gate_staff'))");
            DB::statement("ALTER TABLE registrations ADD CONSTRAINT registrations_status_check CHECK (payment_status IN ('pending','paid','rejected'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('event_assignments');
        Schema::dropIfExists('events');
        Schema::dropIfExists('chapters');
    }
};
