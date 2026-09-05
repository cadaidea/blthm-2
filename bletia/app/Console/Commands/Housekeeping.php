<?php
namespace App\Console\Commands;
use App\Models\Suscriptor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class Housekeeping extends Command
{
    protected $signature = 'digest:housekeeping {--dias-pending=30} {--dry}';
    protected $description = 'Limpia suscriptores pending viejos y marca rebotados duros como bounced';
    public function handle(): int
    {
        $dias = (int) $this->option('dias-pending');
        $dry = $this->option('dry');

        // 1) pending sin confirmar tras N días -> eliminar
        $pending = Suscriptor::where('estado', 'pending')->where('created_at', '<', now()->subDays($dias));
        $nPending = $pending->count();

        // 2) hard bounces -> estado bounced
        $hard = DB::table('bounces')->where('tipo', 'hard')->pluck('email')->unique();
        $nBounce = Suscriptor::whereIn('email', $hard)->where('estado', '!=', 'bounced')->count();

        if ($dry) { $this->info("DRY: borrar pending=$nPending · marcar bounced=$nBounce"); return 0; }

        $pending->delete();
        Suscriptor::whereIn('email', $hard)->where('estado', '!=', 'bounced')->update(['estado' => 'bounced']);
        $this->info("Borrados pending=$nPending · bounced=$nBounce");
        return 0;
    }
}
