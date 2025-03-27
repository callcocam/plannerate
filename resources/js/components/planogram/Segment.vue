<template>
    <div class="segment">
        <LayerVue
            :key="segment.id"
            :shelf="shelf"
            :segment="segment"
            :layer="segment.layer"
            :scale-factor="scaleFactor"
            :gondola="gondola"
            @update:layer="updateLayer"
            @update:segment="updateSegment"
            @transfer-layer="handleTransferLayer"
        />
    </div>
</template>
<script setup lang="ts">
import LayerVue from './Layer.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    gondola: Gondola;
    shelf: Shelf;
    segment: Segment;
    scaleFactor: number;
}>();

interface TransferLayerData {
    layer: Layer;
    segment: Segment;
    fromShelfId: string;
    toShelfId: string;
}

const emit = defineEmits<{
    'update:layer': [layer: Shelf | Layer];
    'update:segment': [segment: Shelf | Segment];
    'transfer-layer': [transferData: TransferLayerData];
}>();

// Função para passar atualizações para o componente pai
const updateLayer = (layer: Shelf | Layer) => {
    emit('update:layer', layer);
};

const updateSegment = (segment: Shelf | Segment) => {
    emit('update:segment', segment);
};

// Função para repassar eventos de transferência de layer
const handleTransferLayer = (transferData: TransferLayerData) => {
    // @ts-ignore
    router.put(route('segments.shelf-update',transferData.segment.id), { 
        layerId: transferData.layer.id,
        toShelfId: transferData.toShelfId,// para onde está indo 
        segmentId:  props.shelf.section_id,// qual segmento está indo
    },{
        preserveState: false,
        onSuccess: () => {
            console.log('Transferência de layer concluída com sucesso');
        },
        onError: () => {
            console.error('Erro ao transferir layer');
        }
    });
    // emit('transfer-layer', transferData);
};
</script>
