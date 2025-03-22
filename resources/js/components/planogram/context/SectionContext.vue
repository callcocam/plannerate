<template>
    <ContextMenu>
        <ContextMenuTrigger>
            <slot></slot>
        </ContextMenuTrigger>
        <ContextMenuContent class="w-64">
            <ContextMenuLabel>Seção: {{ section.name }}</ContextMenuLabel>
            <ContextMenuSeparator />

            <ContextMenuItem @click="duplicateSection">
                Duplicar Seção
                <ContextMenuShortcut>⌘D</ContextMenuShortcut>
            </ContextMenuItem>

            <ContextMenuSub>
                <ContextMenuSubTrigger>Adicionar</ContextMenuSubTrigger>
                <ContextMenuSubContent class="w-48">
                    <ContextMenuItem @click="addShelf">
                        Nova Prateleira
                    </ContextMenuItem>
                    <ContextMenuItem @click="addProductOnSection">
                        Produto
                    </ContextMenuItem>
                </ContextMenuSubContent>
            </ContextMenuSub>

            <ContextMenuSeparator />
            <ContextMenuItem @click="deleteSection" class="text-red-500">
                Excluir
                <ContextMenuShortcut>⌫</ContextMenuShortcut>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3'
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuSeparator,
    ContextMenuShortcut,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from '@/components/ui/context-menu'

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
    gondola: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['duplicate', 'delete', 'add-shelf', 'add-product', 'update:section', 'move', 'align']);
 
// Duplicar a seção com todos os seus componentes
const duplicateSection = () => {
    router.post(route('sections.duplicate', { section: props.section.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Emite evento para atualizar a UI
            emit('duplicate', props.section);
        }
    });
};

// Excluir a seção
const deleteSection = () => {
    if (confirm(`Tem certeza que deseja excluir a seção "${props.section.name}"?`)) {
        router.delete(route('sections.destroy', { section: props.section.id }), {
            preserveScroll: true,
            onSuccess: () => {
                emit('delete', props.section.id);
            }
        });
    }
};

// Adicionar nova prateleira na seção
const addShelf = () => {
    // Calcula uma posição padrão para a nova prateleira
    const defaultPosition = props.gondola.base_height + 30; // 30cm acima da base

    router.post(route('shelves.store'), {
        section_id: props.section.id,
        position: defaultPosition,
        height: 2,
        depth: props.section.depth,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('add-shelf');
        }
    });
};

// Adicionar produto diretamente na seção
const addProductOnSection = () => {
    emit('add-product', props.section);
};
 

</script>
<style scoped></style>