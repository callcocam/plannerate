<script setup>
/**
 * Componente para configuração de seções de gôndola
 *
 * Este componente implementa um wizard de 3 passos para configurar uma seção de gôndola
 * com controles para dimensões, número de prateleiras, espaçamento e visualização
 */
import { Button } from '@/components/ui/button';
import { SmallInput } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Check, ChevronLeft, ChevronRight, Layers, Ruler, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Props e Emits
const props = defineProps({
    gondola: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save', 'cancel']);

// Estado do componente
const step = ref(1);
const totalSteps = 3;

const { gondola } = props;

// Dados da seção sendo configurada
const sectionData = ref({
    name: 'Modulo 1',
    width: gondola?.width || 130,
    height: gondola.height - 10, // Um pouco menor que a altura da gôndola
    depth: 40,
    shelfCount: 5,
    hole_spacing: props.gondola.hole_spacing || 2,
    base_height: props.gondola.base_height || 17,
    position: 0,
    is_hanging: false, // Para produtos penduráveis
    modulos: 4,
    shelf_height: gondola.shelf_height || 4,
    thickness: gondola.thickness || 2,
});

/**
 * Cálculo das posições das prateleiras
 * - Primeira prateleira na base
 * - Última prateleira no topo
 * - Prateleiras intermediárias distribuídas uniformemente
 */
const shelfPositions = computed(() => {
    const positions = [];
    const availableHeight = sectionData.value.height - sectionData.value.base_height;

    // Caso especial: Se só tiver uma prateleira, posicione na base
    if (sectionData.value.shelfCount === 1) {
        positions.push(sectionData.value.base_height);
        return positions;
    }

    // Caso especial: Se tiver duas prateleiras, uma fica na base e outra no topo
    if (sectionData.value.shelfCount === 2) {
        positions.push(sectionData.value.base_height); // Primeira na base
        positions.push(sectionData.value.height - sectionData.value.shelf_height); // Última no topo
        return positions;
    }

    // Para 3 ou mais prateleiras
    // Primeira prateleira na base
    positions.push(sectionData.value.base_height);

    // Calcular o espaçamento para as prateleiras intermediárias
    const middleShelfCount = sectionData.value.shelfCount - 2; // Excluindo a primeira e a última
    const middleSpacing = (availableHeight - sectionData.value.shelf_height) / (middleShelfCount + 1);

    // Adicionar prateleiras intermediárias
    for (let i = 1; i <= middleShelfCount; i++) {
        const position = sectionData.value.base_height + middleSpacing * i;
        positions.push(position);
    }

    // Última prateleira no topo
    positions.push(sectionData.value.height - sectionData.value.shelf_height);

    return positions;
});

/**
 * Navegação entre etapas do wizard
 */
const nextStep = () => {
    if (step.value < totalSteps) {
        step.value += 1;
    }
};

const prevStep = () => {
    if (step.value > 1) {
        step.value -= 1;
    }
};

/**
 * Salva a configuração da seção
 * Utiliza as posições calculadas em shelfPositions
 */
const saveSection = () => {
    const shelves = [];

    // Usar os mesmos cálculos de posição que usamos no preview
    shelfPositions.value.forEach((position) => {
        shelves.push({
            position,
            height: sectionData.value.shelf_height,
        });
    });

    const finalData = {
        ...sectionData.value,
        shelves,
    };

    emit('save', finalData);
};

/**
 * Validação de formulário para controlar a navegação
 */
const canAdvance = computed(() => {
    if (step.value === 1) {
        return sectionData.value.name.trim() !== '';
    }
    return true;
});

/**
 * Estilos para preview visual
 */
// Estilo do container da seção
const sectionPreviewStyle = computed(() => {
    const ratio = 100 / sectionData.value.height; // Escala para o preview
    const width = sectionData.value.width * ratio;

    return {
        width: `${width}px`,
        maxWidth: '100%',
        height: '100px', // Altura fixa para o preview
        backgroundColor: '#f9fafb',
        border: '1px solid #e5e7eb',
        position: 'relative',
    };
});

// Estilo da base da gôndola
const baseStyle = computed(() => {
    const ratio = 100 / sectionData.value.height;
    const baseHeight = sectionData.value.base_height * ratio;

    return {
        position: 'absolute',
        left: '0',
        bottom: '0',
        width: '100%',
        height: `${baseHeight}px`,
        backgroundColor: '#d1d5db',
        borderTop: '1px solid #9ca3af',
    };
});

/**
 * Calcula estilo para cada prateleira no preview
 */
const getShelfStyle = (index) => {
    const ratio = 100 / sectionData.value.height;
    const position = shelfPositions.value[index];

    return {
        position: 'absolute',
        left: '0',
        bottom: `${position * ratio}px`,
        width: '100%',
        height: '3px',
        backgroundColor: '#9ca3af',
    };
};

// Array para renderizar o número correto de prateleiras
const shelvesArray = computed(() => Array(sectionData.value.shelfCount).fill(null));

/**
 * Calcula o espaçamento entre prateleiras para exibir no resumo
 */
const shelfSpacingSummary = computed(() => {
    if (sectionData.value.shelfCount <= 1) {
        return 'N/A';
    }

    if (sectionData.value.shelfCount === 2) {
        const distance = sectionData.value.height - sectionData.value.base_height - sectionData.value.shelf_height;
        return distance.toFixed(1) + 'cm';
    }

    const availableHeight = sectionData.value.height - sectionData.value.base_height;
    const middleShelfCount = sectionData.value.shelfCount - 2;
    const middleSpacing = (availableHeight - sectionData.value.shelf_height) / (middleShelfCount + 1);

    return middleSpacing.toFixed(1) + 'cm';
});
</script>

<template>
    <div class="space-y-4 py-2">
        <!-- Indicadores de progresso -->
        <div class="mb-4 flex justify-between">
            <div class="flex items-center">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full"
                    :class="step >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                >
                    <span v-if="step > 1">
                        <Check class="h-4 w-4" />
                    </span>
                    <span v-else>1</span>
                </div>
                <div class="mx-2 h-0.5 w-8" :class="step > 1 ? 'bg-primary' : 'bg-muted'"></div>
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full"
                    :class="step >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                >
                    <span v-if="step > 2">
                        <Check class="h-4 w-4" />
                    </span>
                    <span v-else>2</span>
                </div>
                <div class="mx-2 h-0.5 w-8" :class="step > 2 ? 'bg-primary' : 'bg-muted'"></div>
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full"
                    :class="step >= 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                >
                    <span>3</span>
                </div>
            </div>
        </div>

        <!-- Passo 1: Informações básicas da seção -->
        <div v-if="step === 1" class="space-y-4">
            <h3 class="flex items-center text-lg font-medium">
                <span class="mr-2 flex-shrink-0 rounded bg-primary/10 p-1">
                    <Layers class="h-5 w-5 text-primary" />
                </span>
                Informações da Seção
            </h3>
            <Separator />
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Largura (cm)</label>
                        <SmallInput type="number" v-model="sectionData.width" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Profundidade (cm)</label>
                        <SmallInput type="number" v-model="sectionData.depth" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Espessura (cm)</label>
                        <SmallInput type="number" v-model="sectionData.shelf_height" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 2: Configuração de prateleiras -->
        <div v-if="step === 2" class="space-y-4">
            <h3 class="flex items-center text-lg font-medium">
                <span class="mr-2 flex-shrink-0 rounded bg-primary/10 p-1">
                    <Ruler class="h-5 w-5 text-primary" />
                </span>
                Configuração de Prateleiras
            </h3>
            <Separator />
            <div class="flex space-x-4">
                <!-- Controles de configuração -->
                <div class="flex-1 space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nº de Prateleiras</label>
                            <SmallInput type="number" v-model="sectionData.shelfCount" min="1" max="10" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Espaçamento entre furos</label>
                            <SmallInput type="number" v-model="sectionData.hole_spacing" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Altura da Base</label>
                            <SmallInput type="number" v-model="sectionData.base_height" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nº de Módulos</label>
                            <SmallInput type="number" v-model="sectionData.modulos" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Espessura da coluna</label>
                            <SmallInput type="number" v-model="sectionData.thickness" />
                        </div>
                    </div>

                    <!-- Opção de produto pendurável -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tipo de Produto</label>
                        <div class="grid grid-cols-2 gap-2">
                            <Button :variant="!sectionData.is_hanging ? 'default' : 'outline'" @click="sectionData.is_hanging = false">
                                Normal
                            </Button>
                            <Button :variant="sectionData.is_hanging ? 'default' : 'outline'" @click="sectionData.is_hanging = true">
                                Pendurável
                            </Button>
                        </div>
                    </div>

                    <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                        <p>
                            As prateleiras serão distribuídas com a primeira na base e a última no topo, com as intermediárias distribuídas
                            uniformemente.
                        </p>
                    </div>
                </div>

                <!-- Preview visual da configuração -->
                <div class="flex w-48 flex-col items-center">
                    <h4 class="mb-2 text-sm font-medium">Visualização</h4>
                    <div :style="sectionPreviewStyle" class="relative border border-gray-300">
                        <!-- Base da gôndola -->
                        <div :style="baseStyle" class="base-preview"></div>

                        <!-- Prateleiras -->
                        <Skeleton v-for="(_, index) in shelvesArray" :key="index" :style="getShelfStyle(index)" class="shelf-preview" />
                    </div>
                    <div class="mt-2 text-center text-xs text-gray-500" v-if="sectionData.shelfCount > 1">
                        Espaçamento entre prateleiras: {{ shelfSpacingSummary }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 3: Revisão e finalização -->
        <div v-if="step === 3" class="space-y-4">
            <h3 class="flex items-center text-lg font-medium">
                <span class="mr-2 flex-shrink-0 rounded bg-primary/10 p-1">
                    <Check class="h-5 w-5 text-primary" />
                </span>
                Revisão
            </h3>
            <Separator />
            <div class="space-y-4 rounded-lg bg-muted/50 p-4">
                <!-- Sumário das configurações -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-sm font-medium">Informações Básicas</h4>
                        <ul class="mt-2 space-y-1">
                            <li class="text-sm">
                                Nome: <span class="font-medium">{{ sectionData.name }}</span>
                            </li>
                            <li class="text-sm">
                                Largura: <span class="font-medium">{{ sectionData.width }}cm</span>
                            </li>
                            <li class="text-sm">
                                Altura: <span class="font-medium">{{ sectionData.height }}cm</span>
                            </li>
                            <li class="text-sm">
                                Profundidade: <span class="font-medium">{{ sectionData.depth }}cm</span>
                            </li>
                            <li class="text-sm">
                                Altura da Base: <span class="font-medium">{{ sectionData.base_height }}cm</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium">Configuração de Prateleiras</h4>
                        <ul class="mt-2 space-y-1">
                            <li class="text-sm">
                                Quantidade: <span class="font-medium">{{ sectionData.shelfCount }}</span>
                            </li>
                            <li class="text-sm">Distribuição: <span class="font-medium">Base ao Topo</span></li>
                            <li class="text-sm">
                                Tipo: <span class="font-medium">{{ sectionData.is_hanging ? 'Pendurável' : 'Normal' }}</span>
                            </li>
                            <li class="text-sm">
                                Espaçamento médio: <span class="font-medium">{{ shelfSpacingSummary }}</span>
                            </li>
                            <li class="text-sm">
                                Nº de módulos: <span class="font-medium">{{ sectionData.modulos }}</span>
                            </li>
                            <li class="text-sm">
                                Espaçamento entre furos: <span class="font-medium">{{ sectionData.hole_spacing }}cm</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Preview final com todos os módulos -->
                <div class="mt-4 flex justify-center">
                    <div class="preview-container flex w-full max-w-xs overflow-x-auto">
                        <div
                            v-for="(_, mIndex) in sectionData.modulos"
                            :key="mIndex"
                            :style="sectionPreviewStyle"
                            class="relative mx-1 border border-gray-300"
                        >
                            <!-- Base da gôndola -->
                            <div :style="baseStyle" class="base-preview"></div>

                            <!-- Prateleiras -->
                            <Skeleton v-for="(_, index) in shelvesArray" :key="index" :style="getShelfStyle(index)" class="shelf-preview" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de navegação -->
        <div class="flex justify-between pt-4">
            <div>
                <Button v-if="step > 1" variant="outline" @click="prevStep" class="flex items-center">
                    <ChevronLeft class="mr-1 h-4 w-4" />
                    Anterior
                </Button>
                <Button v-else variant="outline" @click="emit('cancel')"> Cancelar </Button>
            </div>
            <div>
                <Button v-if="step < totalSteps" @click="nextStep" :disabled="!canAdvance" class="flex items-center">
                    Próximo
                    <ChevronRight class="ml-1 h-4 w-4" />
                </Button>
                <Button v-else @click="saveSection" variant="default" class="flex items-center">
                    <Save class="mr-1 h-4 w-4" />
                    Salvar Seção
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shelf-preview {
    transition: all 0.3s ease-out;
    height: 3px !important;
}

.preview-container {
    overflow-x: auto;
    padding: 1rem;
}

.base-preview {
    transition: all 0.3s ease-out;
    background-color: #d1d5db;
    border-top: 1px solid #9ca3af;
}
</style>
