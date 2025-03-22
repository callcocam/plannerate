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
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuSeparator,
    ContextMenuShortcut,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
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

    // Se não tiver configuração de alinhamento, definir como centralizado por padrão
    if (!settings.horizontal_alignment) {
        updateLayerSettings({
            horizontal_alignment: 'center',
            justify: false,
        });
    }
});

// Atualizar as configurações da camada
const updateLayerSettings = (newSettings: any) => {
    try {
        let currentSettings: any = props.layer.settings;

        if (typeof currentSettings === 'string') {
            try {
                currentSettings = JSON.parse(currentSettings);
            } catch (e) {
                currentSettings = {};
            }
        } else if (!currentSettings) {
            currentSettings = {};
        }

        const updatedSettings = { ...currentSettings, ...newSettings };

        // Criar dados para atualização
        let data: any = { ...props.layer };
        data.settings = updatedSettings;

        // Salvar no servidor
        router.put(route('layers.update', { layer: props.layer.id }), data, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: ({ props: responseProps }) => {
                // Se o servidor retornar a camada atualizada, use-a
                if (responseProps?.record) {
                    emit('align', {
                        layer: responseProps.record,
                        position: newSettings.horizontal_alignment,
                    });
                } else {
                    // Caso contrário, apenas emita a posição para atualizações de frontend
                    emit('align', {
                        layer: props.layer,
                        position: newSettings.horizontal_alignment,
                    });
                }
            },
        });
    } catch (error) {
        console.error('Erro ao atualizar configurações da camada:', error);
    }
};

const deleteProduct = () => {
    if (confirm('Tem certeza que deseja remover este produto?')) {
        router.delete(route('layers.destroy', { layer: props.layer.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emit('delete', props.layer);
            },
        });
    }
};
</script>
<style scoped></style>
