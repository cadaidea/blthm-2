<div
    x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        drawing: false,
        ctx: null,
        ready: false,
        setup() {
            const c = this.$refs.canvas;
            if (!c || c.offsetWidth === 0) { return false; }  // aún no visible
            const ratio = window.devicePixelRatio || 1;
            c.width = c.offsetWidth * ratio;
            c.height = c.offsetHeight * ratio;
            this.ctx = c.getContext('2d');
            this.ctx.scale(ratio, ratio);
            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#161921';
            this.ready = true;
            return true;
        },
        ensure() {
            if (this.ready) return;
            // reintenta hasta que el canvas tenga tamaño (modal ya visible)
            let tries = 0;
            const iv = setInterval(() => {
                if (this.setup() || tries++ > 40) clearInterval(iv);
            }, 100);
        },
        pos(e) {
            const r = this.$refs.canvas.getBoundingClientRect();
            const t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - r.left, y: t.clientY - r.top };
        },
        start(e) { e.preventDefault(); if (!this.ready) this.setup(); if (!this.ready) return; this.drawing = true; const p = this.pos(e); this.ctx.beginPath(); this.ctx.moveTo(p.x, p.y); },
        move(e) { if (!this.drawing) return; e.preventDefault(); const p = this.pos(e); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); },
        end() { if (!this.drawing) return; this.drawing = false; this.state = this.$refs.canvas.toDataURL('image/png'); },
        clear() { if (!this.ctx) return; const c = this.$refs.canvas; this.ctx.clearRect(0,0,c.width,c.height); this.state = null; }
    }"
    x-init="ensure()"
    wire:ignore
>
    <div style="border:2px dashed #bbb;border-radius:10px;background:#fff;overflow:hidden">
        <canvas x-ref="canvas"
            style="width:100%;height:200px;display:block;touch-action:none;cursor:crosshair;background:#fff"
            @mousedown="start($event)" @mousemove="move($event)" @mouseup="end()" @mouseleave="end()"
            @touchstart="start($event)" @touchmove="move($event)" @touchend="end()"></canvas>
    </div>
    <button type="button" @click="clear()"
        style="margin-top:8px;background:#eef0f3;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.85rem;color:#444">
        Borrar firma
    </button>
    <p style="color:#999;font-size:.78rem;margin-top:4px">Firma aquí con el dedo o el mouse.</p>
</div>
