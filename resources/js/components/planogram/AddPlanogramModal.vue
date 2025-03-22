<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>Adicionar Seção à Gôndola</DialogTitle>
                <DialogDescription>
                    Configure os detalhes da nova seção para o planograma
                </DialogDescription>
            </DialogHeader>
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid grid-cols-2">
                    <TabsTrigger value="wizard">Assistente</TabsTrigger>
                    <TabsTrigger value="blank">Em branco</TabsTrigger>
                </TabsList>
                <TabsContent value="wizard">
                    <SectionWizard :gondola="gondola" @save="handleSave" @cancel="handleCancel" />
                </TabsContent>
                <TabsContent value="blank">
                    <div class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Nome da Seção</label>
                                <SmallInput v-model="newSection.name" placeholder="Nome da seção" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Largura (cm)</label>
                                <SmallInput type="number" v-model="newSection.width" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Altura (cm)</label>
                                <SmallInput type="number" v-model="newSection.height" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Profundidade (cm)</label>
                                <SmallInput type="number" v-model="newSection.depth" />
                            </div>
                        </div>
                        <div class="flex justify-between pt-4">
                            <Button variant="outline" @click="handleCancel">
                                Cancelar
                            </Button>
                            <Button @click="handleSave(newSection)">
                                Adicionar Seção
                            </Button>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue';
import { SmallInput } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import SectionWizard from './SectionWizard.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    gondola: {
        type: Object,
        required: true
    }
});

const emits = defineEmits(['update:open', 'save', 'cancel']);

// Local reactive state
const isOpen = ref(props.open);
const activeTab = ref('wizard');
const newSection = ref({
    name: '',
    width: 130,
    height: 180,
    depth: 40,
    position: 0,
});

// Watch for external changes to open prop
watch(() => props.open, (newVal) => {
    isOpen.value = newVal;
});

// Watch for internal changes to isOpen
watch(isOpen, (newVal) => {
    emits('update:open', newVal);
});

// Handler functions
function handleSave(section) { 
    emits('save', section);
    isOpen.value = false;
}

function handleCancel() {
    emits('cancel');
    isOpen.value = false;
}
</script>
