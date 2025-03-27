<template>
    <ContextMenu>
        <ContextMenuTrigger>
            <slot />
        </ContextMenuTrigger>
        <ContextMenuContent class="w-64">
            <ContextMenuLabel>Produto </ContextMenuLabel>
            <ContextMenuSeparator />

            <!-- Removidas as opções de alinhamento horizontal -->

            <ContextMenuSeparator />
            <ContextMenuItem @click="deleteProduct" class="text-red-500">
                Excluir
                <ContextMenuShortcut>⌫</ContextMenuShortcut>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<script setup lang="ts">
// @ts-ignore
import {ContextMenu,ContextMenuContent,ContextMenuItem,ContextMenuLabel,ContextMenuSeparator,ContextMenuShortcut,ContextMenuTrigger} from '@/components/ui/context-menu';
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

import { Gondola, Layer, Shelf } from '../planogram';
const props = defineProps<{ gondola: Gondola; shelf: Shelf; layer: Layer }>();

const emit = defineEmits(['update:layer', 'delete', 'align']);

// Configurar alinhamento centralizado como padrão ao montar o componente
onMounted(() => {
    // Verificar se já tem configuração de alinhamento
    let settings: any = props.layer.settings;

    if (typeof settings === 'string') {
        try {
            settings = JSON.parse(settings);
        } catch (e) {
            settings = {};
        }
    } else if (!settings) {
        settings = {};
    }
 
});
 

const deleteProduct = () => {
    if (confirm('Tem certeza que deseja remover este produto?')) {
        // Enviar requisição para deletar o produto
        // @ts-ignore
        router.delete(route('layers.destroy', { layer: props.layer.id }), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                emit('delete', props.layer);
            },
        });
    }
};
</script>
<style scoped></style>
