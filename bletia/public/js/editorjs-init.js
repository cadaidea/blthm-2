document.addEventListener('alpine:init', () => {
    Alpine.data('bletiaEditorJs', (config) => ({
        editor: null,
        fieldId: config.fieldId,
        statePath: config.statePath,
        initialRaw: config.initial,
        uploadImageUrl: config.uploadImageUrl,
        uploadFileUrl: config.uploadFileUrl,
        fetchUrlEndpoint: config.fetchUrlEndpoint,

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        parseInitial() {
            const initial = this.initialRaw;
            if (!initial) return { blocks: [] };
            if (typeof initial === 'object') return initial;
            try {
                const parsed = JSON.parse(initial);
                if (parsed && Array.isArray(parsed.blocks)) return parsed;
            } catch (e) {}
            if (typeof initial === 'string' && initial.trim() !== '') {
                return { blocks: [{ type: 'raw', data: { html: initial } }] };
            }
            return { blocks: [] };
        },

        init() {
            const self = this;
            const EJ = window.BletiaEditorJS;
            if (!EJ) { console.error('BletiaEditorJS bundle no cargó'); return; }

            // Flush: en cualquier mousedown (antes del click real), guarda de inmediato
            // el contenido actual — evita perder texto si el usuario da clic en "Guardar"
            // antes de que termine el debounce interno de Editor.js.
            this.flushListener = () => {
                if (!self.editor) return;
                self.editor.save().then((data) => {
                    self.$wire.set(self.statePath, JSON.stringify(data), false);
                }).catch(() => {});
            };
            document.addEventListener('mousedown', this.flushListener, true);
            const holderEl = this.$refs.holder;
            if (!holderEl) { console.error('Holder ref no encontrado'); return; }
            // Evita doble instancia si Livewire vuelve a montar el mismo nodo
            if (holderEl.dataset.ejMounted === '1') return;
            holderEl.dataset.ejMounted = '1';

            this.editor = new EJ.EditorJS({
                holder: holderEl,
                autofocus: false,
                data: this.parseInitial(),
                placeholder: 'Escribe aquí o presiona "/" para elegir un bloque…',
                i18n: {
                    messages: {
                        ui: {
                            blockTunes: { toggler: { 'Click to tune': 'Ajustes de bloque', 'or drag to move': 'o arrastra para mover' } },
                            inlineToolbar: { converter: { 'Convert to': 'Convertir a' } },
                            toolbar: { toolbox: { Add: 'Añadir bloque' } },
                            popover: { Filter: 'Filtrar', 'Nothing found': 'Sin resultados' },
                        },
                        toolNames: {
                            Text: 'Párrafo', Heading: 'Encabezado', List: 'Lista', Warning: 'Aviso',
                            Checklist: 'Checklist', Quote: 'Cita', Code: 'Código', Delimiter: 'Separador',
                            'Raw HTML': 'HTML crudo', Table: 'Tabla', Link: 'Enlace', Marker: 'Resaltado',
                            Bold: 'Negrita', Italic: 'Cursiva', InlineCode: 'Código en línea',
                            Image: 'Imagen', Underline: 'Subrayado', Attaches: 'Adjunto',
                        },
                        tools: {
                            warning: { Title: 'Título', Message: 'Mensaje' },
                            link: { 'Add a link': 'Añadir un enlace' },
                            stub: { 'The block can not be displayed correctly.': 'Este bloque no se puede mostrar.' },
                        },
                        blockTunes: {
                            delete: { Delete: 'Eliminar' },
                            moveUp: { 'Move up': 'Subir' },
                            moveDown: { 'Move down': 'Bajar' },
                        },
                    },
                },
                tools: {
                    header: { class: EJ.Header, inlineToolbar: ['bold', 'italic', 'link'], config: { levels: [2, 3, 4], defaultLevel: 2, placeholder: 'Título de sección' } },
                    list: { class: EJ.List, inlineToolbar: true },
                    checklist: { class: EJ.Checklist, inlineToolbar: true },
                    quote: { class: EJ.Quote, inlineToolbar: true, config: { quotePlaceholder: 'Escribe la cita', captionPlaceholder: 'Autor / fuente' } },
                    table: { class: EJ.Table, inlineToolbar: true, config: { rows: 2, cols: 3 } },
                    delimiter: EJ.Delimiter,
                    code: { class: EJ.CodeTool, config: { placeholder: 'Código…' } },
                    warning: { class: EJ.Warning, inlineToolbar: true, config: { titlePlaceholder: 'Título', messagePlaceholder: 'Mensaje' } },
                    marker: { class: EJ.Marker },
                    inlineCode: { class: EJ.InlineCode },
                    underline: { class: EJ.Underline },
                    raw: { class: EJ.RawTool, config: { placeholder: 'Pega HTML crudo aquí' } },
                    image: {
                        class: EJ.ImageTool,
                        config: {
                            captionPlaceholder: 'Pie de foto (opcional)',
                            uploader: {
                                uploadByFile(file) {
                                    const fd = new FormData();
                                    fd.append('image', file);
                                    return fetch(self.uploadImageUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': self.csrf() } }).then(r => r.json());
                                },
                                uploadByUrl(url) {
                                    return fetch(self.uploadImageUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': self.csrf() }, body: JSON.stringify({ url }) }).then(r => r.json());
                                },
                            },
                        },
                    },
                    attaches: {
                        class: EJ.AttachesTool,
                        config: {
                            uploader: {
                                uploadByFile(file) {
                                    const fd = new FormData();
                                    fd.append('file', file);
                                    return fetch(self.uploadFileUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': self.csrf() } }).then(r => r.json());
                                },
                            },
                        },
                    },
                    linkTool: { class: EJ.LinkTool, config: { endpoint: this.fetchUrlEndpoint } },
                    embed: {
                        class: EJ.Embed,
                        config: {
                            services: {
                                youtube: true, vimeo: true, 'twitch-video': true, instagram: true, facebook: true,
                                tiktok: {
                                    regex: /https?:\/\/(?:www\.|vm\.)?tiktok\.com\/(?:@[\w.-]+\/video\/(\d+)|(\w+))/,
                                    embedUrl: 'https://www.tiktok.com/embed/v2/<%= remote_id %>',
                                    html: "<iframe style='width:100%; height:740px;' scrolling='no' allowfullscreen></iframe>",
                                    height: 740, width: 325,
                                    id: (ids) => ids[0] || ids[1],
                                },
                                dailymotion: {
                                    regex: /https?:\/\/(?:www\.)?dailymotion\.com\/video\/([a-zA-Z0-9]+)/,
                                    embedUrl: 'https://www.dailymotion.com/embed/video/<%= remote_id %>',
                                    html: "<iframe width='580' height='320' frameborder='0' allowfullscreen></iframe>",
                                    height: 320, width: 580,
                                    id: (ids) => ids[0],
                                },
                            },
                        },
                    },
                },
                onChange: async () => {
                    const data = await self.editor.save();
                    self.$wire.set(self.statePath, JSON.stringify(data), false);
                },
            });
        },

        destroy() {
            if (this.flushListener) document.removeEventListener('mousedown', this.flushListener, true);
            if (this.editor && this.editor.destroy) this.editor.destroy();
        },
    }));
});
