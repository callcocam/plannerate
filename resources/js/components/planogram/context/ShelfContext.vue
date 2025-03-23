<template>
    <ContextMenu>
        <ContextMenuTrigger>
            <slot />
        </ContextMenuTrigger>
        <ContextMenuContent class="w-64">
            <ContextMenuLabel>Prateleira </ContextMenuLabel>
            <ContextMenuSeparator />

            <ContextMenuItem @click="copyShelf">
                Duplicar
                <ContextMenuShortcut>⌘D</ContextMenuShortcut>
            </ContextMenuItem>

            <ContextMenuSub>
                <ContextMenuSubTrigger>Posição</ContextMenuSubTrigger>
                <ContextMenuSubContent class="w-48">
                    <ContextMenuItem @click="moveShelf('up')">
                        Mover para cima
                        <ContextMenuShortcut>↑</ContextMenuShortcut>
                    </ContextMenuItem>
                    <ContextMenuItem @click="moveShelf('down')">
                        Mover para baixo
                        <ContextMenuShortcut>↓</ContextMenuShortcut>
                    </ContextMenuItem>
                    <ContextMenuSeparator />
                    <ContextMenuItem @click="alignShelf"> Alinhar com furos </ContextMenuItem>
                </ContextMenuSubContent>
            </ContextMenuSub>

            <ContextMenuSub>
                <ContextMenuSubTrigger>Configuração</ContextMenuSubTrigger>
                <ContextMenuSubContent class="w-48">
                    <ContextMenuRadioGroup v-model="currentHeight">
                        <ContextMenuRadioItem value="2">Altura padrão (2cm)</ContextMenuRadioItem>
                        <ContextMenuRadioItem value="4">Altura média (4cm)</ContextMenuRadioItem>
                        <ContextMenuRadioItem value="6">Altura grande (6cm)</ContextMenuRadioItem>
                    </ContextMenuRadioGroup>
                </ContextMenuSubContent>
            </ContextMenuSub>

            <ContextMenuSeparator />
            <ContextMenuItem @click="deleteShelf" class="text-red-500">
                Excluir
                <ContextMenuShortcut>⌫</ContextMenuShortcut>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<script setup lang="ts">
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuRadioGroup,
    ContextMenuRadioItem,
    ContextMenuSeparator,
    ContextMenuShortcut,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    shelf: {
        type: Object,
        required: true,
    },
    gondola: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:shelf', 'delete', 'duplicate', 'move', 'align']);

// Usamos ref para rastrear a posição atual da prateleira durante o arraste
const shelfCurrent = ref(props.shelf);

// Refs para controle do menu de contexto
const currentHeight = ref(String(shelfCurrent.value.height || '2'));

// Configurar distribuição proporcional como padrão ao montar o componente
onMounted(() => {
    // Verificar se já tem configuração de distribuição
    const settings = getShelfSettings();

    // Se não tiver configuração de distribuição, definir como 'proportional' por padrão
    if (!settings.distributionMode) {
        // updateShelfSettings({ distributionMode: 'proportional' });
    }
});

// Opções de distribuição de espaço
const getShelfSettings = () => {
    if (!shelfCurrent.value.settings) return {};

    if (typeof shelfCurrent.value.settings === 'string') {
        try {
            return JSON.parse(shelfCurrent.value.settings);
        } catch (e) {
            return {};
        }
    }

    return shelfCurrent.value.settings;
};

// Atualizar as configurações da prateleira
const updateShelfSettings = (newSettings:any) => {
    try {
        const currentSettings = getShelfSettings();
        const updatedSettings = { ...currentSettings, ...newSettings };

        // Criar cópia atualizada da prateleira
        const updatedShelf = {
            ...shelfCurrent.value,
            settings: JSON.stringify(updatedSettings),
        };

        // Emitir evento para atualizar a UI
        emit('update:shelf', updatedShelf);

        // Salvar no servidor se tiver ID
        if (shelfCurrent.value.id) {
            router.put(
                `/shelves/${shelfCurrent.value.id}`,
                {
                    ...shelfCurrent.value,
                    settings: JSON.stringify(updatedSettings),
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('Configurações de prateleira atualizadas com sucesso');
                    },
                    onError: (errors) => {
                        console.error('Erro ao atualizar configurações da prateleira:', errors);
                    },
                },
            );
        }
    } catch (error) {
        console.error('Erro ao atualizar configurações:', error);
    }
};

// Métodos para interatividade do menu de contexto
const copyShelf = () => {
    // Implementação da duplicação de prateleira
    const newShelf = {
        ...shelfCurrent.value,
        id: null, // Novo ID será gerado pelo servidor
        position: shelfCurrent.value.position + 10, // Posiciona um pouco abaixo para diferenciar
    };
    // Opcionalmente, envia para o servidor para persistir
    router.post('/shelves', newShelf, {
        onSuccess: (page: any) => {
            console.log(page.props.record.id);
            emit('duplicate', {
                ...newShelf,
                id: page.props.record.id,
            });
        },
        onError: (errors) => {
            console.error('Erro ao duplicar prateleira:', errors);
        },
    });
};

const deleteShelf = () => {
    // Primeiro emite o evento para atualizar o UI imediatamente
    emit('delete', shelfCurrent.value);

    // Depois faz a chamada de API para persistir a alteração no servidor
    if (shelfCurrent.value.id) {
        router.delete(`/shelves/${shelfCurrent.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Prateleira excluída com sucesso');
            },
            onError: (errors) => {
                console.error('Erro ao excluir prateleira:', errors);
                // If error occurs, we should reload to get the correct state
                router.reload();
            },
        });
    }
};

const moveShelf = (direction: 'up' | 'down') => {
    const moveDistance = props.gondola.hole_spacing; // Move pela distância entre furos
    const newPosition = direction === 'up' ? shelfCurrent.value.position + moveDistance : shelfCurrent.value.position - moveDistance;

    // Verifica limites
    if (newPosition < props.gondola.base_height || newPosition > props.gondola.height) {
        return; // Não permite mover para fora da gôndola
    }
    shelfCurrent.value.position = newPosition;
    emit('update:shelf', { ...shelfCurrent.value, position: newPosition });
    emit('move', { shelf: shelfCurrent.value, direction });
};

const alignShelf = () => {
    emit('align', shelfCurrent.value);
};
</script>
<style scoped></style>
