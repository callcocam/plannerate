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

            <!-- Submenu para mover para diferentes seções -->
            <ContextMenuSub v-if="gondola.sections && gondola.sections.length > 1">
                <ContextMenuSubTrigger>Mover para seção</ContextMenuSubTrigger>
                <ContextMenuSubContent class="max-h-80 w-56 overflow-y-auto">
                    <ContextMenuItem v-for="section in otherSections" :key="section.id" @click="moveToSection(section)">
                        {{ section.name }}
                    </ContextMenuItem>
                    <div v-if="otherSections.length === 0" class="px-2 py-1 text-sm text-gray-500">Nenhuma outra seção disponível</div>
                </ContextMenuSubContent>
            </ContextMenuSub>

            <!-- Inverter a ordem dos layers -->
            <ContextMenuItem @click="inverlLayers" v-if="hasMultipleLayers">
                Inverter ordem
                <ContextMenuShortcut>⇧⌘L</ContextMenuShortcut>
            </ContextMenuItem>
            <ContextMenuSeparator />
            <ContextMenuItem @click="deleteShelf" class="text-red-500">
                Excluir
                <ContextMenuShortcut>⌫</ContextMenuShortcut>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<script setup lang="ts">
// @ts-nocheck
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
} from '@/components/ui/context-menu';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    shelf: {
        type: Object,
        required: true,
    },
    gondola: {
        type: Object,
        required: true,
    },
    shelfDirection: {
        type: String,
        default: 'top',
    },
});

const emit = defineEmits(['update:shelf', 'delete', 'duplicate', 'move', 'align', 'transfer']);

// Usamos ref para rastrear a posição atual da prateleira durante o arraste
const shelfCurrent = ref(props.shelf);

// Verificar se existe mais de uma layer
const hasMultipleLayers = computed(() => {
    if (shelfCurrent.value.segments.length === 0) {
        return false;
    }
    // Se não temos seções ou não temos a seção atual, retorna vazio
    return shelfCurrent.value.segments.map((segment) => segment.layer).length > 1;
});

// Refs para controle do menu de contexto
const currentHeight = ref(String(shelfCurrent.value.height || '2'));

// Computado para filtrar apenas outras seções (diferentes da atual)
const otherSections = computed(() => {
    // Se não temos seções ou não temos a seção atual, retorna vazio
    if (!props.gondola.sections || !shelfCurrent.value.section) {
        return [];
    }

    // Filtra para excluir a seção atual
    return props.gondola.sections.filter((section) => section.id !== shelfCurrent.value.section.id);
});

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
const updateShelfSettings = (newSettings: any) => {
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
    // Depois faz a chamada de API para persistir a alteração no servidor
    if (shelfCurrent.value.id) {
        router.delete(route('shelves.destroy', shelfCurrent.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Prateleira excluída com sucesso');
                // Primeiro emite o evento para atualizar o UI imediatamente
                emit('delete', shelfCurrent.value);
            },
            onError: (errors) => {
                console.error('Erro ao excluir prateleira:', errors);
                // If error occurs, we should reload to get the correct state
                router.reload();
            },
        });
    }
};

// Movimentar prateleira para cima ou para baixo
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

// Mover prateleira para outra seção
const moveToSection = (targetSection) => {
    if (!targetSection || !targetSection.id || !shelfCurrent.value.section) {
        return;
    }

    // Calcula uma posição padrão na nova seção (pode ser melhorada)
    const defaultPosition = calculatePositionInSection(targetSection);

    // Prepara os dados para transferência
    const transferData = {
        shelf: shelfCurrent.value,
        fromSectionId: shelfCurrent.value.section.id,
        toSectionId: targetSection.id,
        position: defaultPosition,
    };

    // Emite o evento de transferência que será capturado pelo componente pai
    emit('transfer', transferData);
};

// Calcula uma posição adequada na seção de destino
const calculatePositionInSection = (targetSection) => {
    // Posição base (acima da base da gôndola)
    const basePosition = props.gondola.base_height;

    // Se a seção já tem prateleiras, calcula posição baseada nelas
    if (targetSection.shelves && targetSection.shelves.length > 0) {
        // Encontra a posição da prateleira mais alta
        const highestShelf = [...targetSection.shelves].sort((a, b) => b.position - a.position)[0];

        // Adiciona um espaçamento adequado acima da prateleira mais alta
        const spacing = props.gondola.hole_spacing || 30;
        return highestShelf.position + spacing;
    }

    // Posição padrão se não houver prateleiras na seção
    return basePosition + 30; // 30cm acima da base (ou outro valor padrão)
};

const alignShelf = () => {
    emit('align', shelfCurrent.value);
};

const inverlLayers = () => {
    const updatedSegments = [...shelfCurrent.value.segments].reverse();
    const updatedShelf = {
        ...shelfCurrent.value,
        segments: updatedSegments,
        invert: true,
    };
    emit('update:shelf', updatedShelf);
};
</script>
<style scoped></style>
