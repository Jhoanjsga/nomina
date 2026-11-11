<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_hora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->decimal('valor', 12, 2);
            $table->date('vigente_desde');
            $table->timestamps();
        });

        Schema::create('responsables', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 45);
            $table->string('tipo', 45);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 45)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->date('fecha_finalizacion');
            $table->decimal('total_horas', 8, 2);
            $table->decimal('total_pagado', 12, 2);
            $table->timestamps();
        });

        Schema::create('registros_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('responsable_id')->constrained('responsables');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('liquidacion_id')->nullable()->constrained('liquidaciones');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('radicado', 100);
            $table->string('cliente', 100);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_horas');
        Schema::dropIfExists('liquidaciones');
        Schema::dropIfExists('items');
        Schema::dropIfExists('responsables');
        Schema::dropIfExists('valores_hora');
    }
};