<?php

namespace App\Services;

use App\Models\WooPedido;
use App\Models\WooPedidoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WooImport
{
    protected static function cfg(string $k, $d = null)
    {
        return class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get($k) ?: $d) : $d;
    }

    protected static function base(): string
    {
        return rtrim((string) self::cfg('woo_url'), '/') . '/wp-json/wc/v3/';
    }

    protected static function req(string $endpoint, array $query = [])
    {
        $key = self::cfg('woo_key');
        $sec = self::cfg('woo_secret');
        return Http::withBasicAuth((string) $key, (string) $sec)
            ->acceptJson()->timeout(40)->get(self::base() . $endpoint, $query);
    }

    public static function probar(): array
    {
        if (! self::cfg('woo_url') || ! self::cfg('woo_key') || ! self::cfg('woo_secret')) {
            return ['ok' => false, 'msg' => 'Faltan credenciales (URL/key/secret).'];
        }
        try {
            $r = self::req('orders', ['per_page' => 1]);
            if ($r->successful()) return ['ok' => true, 'msg' => 'Conexión correcta.'];
            return ['ok' => false, 'msg' => 'HTTP ' . $r->status() . ': ' . Str::limit($r->body(), 160)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public static function importarClientes(int $maxPaginas = 20): array
    {
        $total = 0;
        for ($p = 1; $p <= $maxPaginas; $p++) {
            $r = self::req('customers', ['per_page' => 100, 'page' => $p, 'orderby' => 'id', 'order' => 'asc']);
            if (! $r->successful()) break;
            $items = $r->json();
            if (empty($items)) break;
            foreach ($items as $c) {
                self::upsertCliente($c);
                $total++;
            }
            if (count($items) < 100) break;
        }
        return ['ok' => true, 'total' => $total];
    }

    protected static function upsertCliente(array $c): void
    {
        if (! Schema::hasTable('clientes')) return;
        $email = strtolower((string) ($c['email'] ?? ''));
        if (! $email) return;
        $bill = $c['billing'] ?? [];
        $nombre = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ($bill['company'] ?? $email);

        $datos = ['woo_customer_id' => $c['id'] ?? null];
        $set = fn ($col, $val) => Schema::hasColumn('clientes', $col) && $val !== null && $val !== '' ? [$col => $val] : [];
        $datos += $set('nombre', $nombre);
        $datos += $set('telefono', $bill['phone'] ?? null);
        $datos += $set('celular', $bill['phone'] ?? null);
        $datos += $set('direccion', trim(($bill['address_1'] ?? '') . ' ' . ($bill['address_2'] ?? '')));
        $datos += $set('ciudad', $bill['city'] ?? null);
        $datos += $set('provincia', $bill['state'] ?? null);

        $existe = DB::table('clientes')->where('email', $email)
            ->orWhere(fn ($q) => Schema::hasColumn('clientes', 'woo_customer_id') && ($c['id'] ?? null) ? $q->where('woo_customer_id', $c['id']) : $q->whereRaw('1=0'))
            ->first();

        if ($existe) {
            DB::table('clientes')->where('id', $existe->id)->update($datos + ['updated_at' => now()]);
        } else {
            $base = ['email' => $email, 'created_at' => now(), 'updated_at' => now()];
            if (Schema::hasColumn('clientes', 'password')) $base['password'] = Hash::make(Str::random(24));
            DB::table('clientes')->insert($datos + $base);
        }
    }

    public static function importarPedidos(int $maxPaginas = 40): array
    {
        $total = 0;
        for ($p = 1; $p <= $maxPaginas; $p++) {
            $r = self::req('orders', ['per_page' => 50, 'page' => $p, 'orderby' => 'id', 'order' => 'asc']);
            if (! $r->successful()) break;
            $items = $r->json();
            if (empty($items)) break;
            foreach ($items as $o) {
                self::upsertPedido($o);
                $total++;
            }
            if (count($items) < 50) break;
        }
        return ['ok' => true, 'total' => $total];
    }

    protected static function upsertPedido(array $o): void
    {
        $bill = $o['billing'] ?? [];
        $ped = WooPedido::updateOrCreate(
            ['woo_id' => $o['id']],
            [
                'numero'          => $o['number'] ?? (string) $o['id'],
                'estado'          => $o['status'] ?? null,
                'total'           => $o['total'] ?? 0,
                'moneda'          => $o['currency'] ?? null,
                'cliente_nombre'  => trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? '')) ?: ($bill['company'] ?? null),
                'cliente_email'   => $bill['email'] ?? null,
                'woo_customer_id' => $o['customer_id'] ?? null,
                'fecha'           => isset($o['date_created']) ? \Illuminate\Support\Carbon::parse($o['date_created']) : null,
                'raw'             => $o,
                'importado_en'    => now(),
            ]
        );
        WooPedidoItem::where('woo_pedido_id', $ped->id)->delete();
        foreach (($o['line_items'] ?? []) as $li) {
            $vars = collect($li['meta_data'] ?? [])->map(fn ($m) => ($m['display_key'] ?? $m['key'] ?? '') . ': ' . (is_array($m['display_value'] ?? null) ? json_encode($m['display_value']) : ($m['display_value'] ?? $m['value'] ?? '')))->implode(' · ');
            WooPedidoItem::create([
                'woo_pedido_id'   => $ped->id,
                'producto_nombre' => $li['name'] ?? '',
                'sku'             => $li['sku'] ?? null,
                'cantidad'        => $li['quantity'] ?? 1,
                'precio'          => isset($li['price']) ? (float) $li['price'] : 0,
                'total'           => $li['total'] ?? 0,
                'variaciones'     => $vars ?: null,
            ]);
        }
    }
}
