<x-dynamic-component
    :component="$getFieldWrapperView()"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>
    <div
        wire:ignore
        x-data="bletiaEditorJs({
            fieldId: @js($getId()),
            statePath: @js($getStatePath()),
            initial: @js($getState()),
            uploadImageUrl: @js(route('editorjs.upload-image')),
            uploadFileUrl: @js(route('editorjs.upload-file')),
            fetchUrlEndpoint: @js(route('editorjs.fetch-url')),
        })"
        x-init="window.initLinkSearch($refs.holder, { searchUrl: @js(route('editorjs.content-search')), minLength: 2, debounceMs: 250 })"
        class="ej-field-wrap"
    >
        <div x-ref="holder" class="ej-holder"></div>
    </div>
</x-dynamic-component>
