<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('sri_comprobantes')) {
            Schema::create('sri_comprobantes', function (Blueprint $t) {
                $t->id();
                $t->string('tipo', 20)->index();          // factura, nota_credito, retencion, guia_remision
                $t->string('cod_doc', 2)->index();         // 01,04,07,06
                $t->string('ambiente', 1)->default('1');   // 1 pruebas, 2 produccion
                $t->string('estab', 3)->default('001');
                $t->string('pto_emi', 3)->default('001');
                $t->string('secuencial', 9)->index();
                $t->string('clave_acceso', 49)->unique();
                $t->string('estado', 30)->default('CREADO')->index(); // CREADO,FIRMADO,ENVIADO,RECIBIDA,DEVUELTA,AUTORIZADO,NO_AUTORIZADO,ERROR
                $t->string('numero_autorizacion')->nullable();
                $t->timestamp('fecha_autorizacion')->nullable();
                // relaciones de negocio
                $t->unsignedBigInteger('pedido_id')->nullable()->index();
                $t->unsignedBigInteger('cliente_id')->nullable()->index();
                $t->unsignedBigInteger('comprobante_ref_id')->nullable(); // para NC/retención que referencian factura
                // receptor
                $t->string('receptor_tipo_id', 2)->nullable();  // 04 RUC,05 cedula,06 pasaporte,07 consumidor final
                $t->string('receptor_identificacion')->nullable();
                $t->string('receptor_razon')->nullable();
                $t->string('receptor_email')->nullable();
                $t->string('receptor_direccion')->nullable();
                $t->string('receptor_telefono')->nullable();
                // montos
                $t->decimal('subtotal', 12, 2)->default(0);
                $t->decimal('iva', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->json('detalles')->nullable();   // líneas
                $t->json('extra')->nullable();       // datos específicos por tipo (motivos NC, guía, etc.)
                // archivos
                $t->text('xml_firmado')->nullable();
                $t->text('xml_autorizado')->nullable();
                $t->string('pdf_path')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('sri_secuenciales')) {
            Schema::create('sri_secuenciales', function (Blueprint $t) {
                $t->id();
                $t->string('cod_doc', 2);
                $t->string('estab', 3)->default('001');
                $t->string('pto_emi', 3)->default('001');
                $t->unsignedBigInteger('ultimo')->default(0);
                $t->timestamps();
                $t->unique(['cod_doc', 'estab', 'pto_emi']);
            });
        }
        if (! Schema::hasTable('sri_logs')) {
            Schema::create('sri_logs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('comprobante_id')->nullable()->index();
                $t->string('paso', 30); // clave,xml,firma,recepcion,autorizacion,ride,correo
                $t->string('resultado', 20);
                $t->text('mensaje')->nullable();
                $t->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('sri_logs');
        Schema::dropIfExists('sri_secuenciales');
        Schema::dropIfExists('sri_comprobantes');
    }
};
