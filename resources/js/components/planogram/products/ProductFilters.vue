<template>
    <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-700">
        <!-- Barra de busca -->
        <Input v-model="localSearch" placeholder="Buscar produtos..." class="w-full">
            <template #prefix>
                <Search class="h-4 w-4" />
            </template>
        </Input>

        <!-- Seção de filtros colapsável -->
        <Collapsible v-model:open="showFilters" class="mt-4">
            <CollapsibleTrigger asChild>
                <Button variant="outline" class="flex w-full items-center justify-between">
                    <div class="flex items-center">
                        <SlidersHorizontal class="mr-2 h-4 w-4" />
                        <span>Filtros</span>

                        <!-- Badge com número de filtros ativos -->
                        <Badge v-if="activeFiltersCount > 0" class="ml-2" variant="secondary">
                            {{ activeFiltersCount }}
                        </Badge>
                    </div>
                    <ChevronDown class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': showFilters }" />
                </Button>
            </CollapsibleTrigger>

            <CollapsibleContent class="mt-4 space-y-4 p-4">
                <!-- Filtro de categoria -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>

                    <!-- Fixed Select implementation -->
                    <Select :modelValue="localCategory" @update:modelValue="handleCategoryChange" class="z-[120]">
                        <SelectTrigger class="w-full">
                            <SelectValue :placeholder="getCategoryPlaceholder()" />
                        </SelectTrigger>
                        <SelectContent class="z-[120]">
                            <SelectItem :value="null">Todas as categorias</SelectItem>
                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Filtros de atributos -->
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Atributos</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localHangable" id="hangable" />
                            <label for="hangable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Pendurável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localStackable" id="stackable" />
                            <label for="stackable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Empilhável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localFlammable" id="flammable" />
                            <label for="flammable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Inflamável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localPerishable" id="perishable" />
                            <label for="perishable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Perecível</label>
                        </div>
                    </div>
                </div>

                <!-- Ações de filtro -->
                <div class="flex justify-end">
                    <Button variant="outline" size="sm" @click="clearFilters">Limpar filtros</Button>
                </div>
            </CollapsibleContent>
        </Collapsible>
    </div>
</template>

<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronDown, Search, SlidersHorizontal } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    activeFiltersCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:filters', 'clear']);

// Estado local
const showFilters = ref(false);

// Controladores de estado individual para cada filtro
const localHangable = ref(props.filters.hangable);
const localStackable = ref(props.filters.stackable);
const localFlammable = ref(props.filters.flammable);
const localPerishable = ref(props.filters.perishable);
const localSearch = ref(props.filters.search);
const localCategory = ref(props.filters.category);

// Função para obter o placeholder correto baseado na seleção atual
const getCategoryPlaceholder = () => {
    if (!localCategory.value) return "Selecionar categoria";
    
    const selected = props.categories.find(cat => cat.id === localCategory.value);
    return selected ? selected.name : "Selecionar categoria";
};

// Handler específico para mudança de categoria
const handleCategoryChange = (newValue) => {
    localCategory.value = newValue;
    updateFilters();
};

// Observar mudanças nos filtros vindas do componente pai
watch(
    () => props.filters,
    (newFilters) => {
        localHangable.value = newFilters.hangable;
        localStackable.value = newFilters.stackable;
        localFlammable.value = newFilters.flammable;
        localPerishable.value = newFilters.perishable;
        localSearch.value = newFilters.search;
        localCategory.value = newFilters.category;
    },
    { deep: true },
);

// Observar mudanças nos filtros locais
watch([localHangable, localStackable, localFlammable, localPerishable, localSearch, localCategory], () => {
    updateFilters();
});

/**
 * Atualiza os filtros no componente pai
 */
function updateFilters() {
    emit('update:filters', {
        hangable: localHangable.value,
        stackable: localStackable.value,
        flammable: localFlammable.value,
        perishable: localPerishable.value,
        search: localSearch.value,
        category: localCategory.value,
    });
}

/**
 * Limpa todos os filtros
 */
function clearFilters() {
    localHangable.value = false;
    localStackable.value = false;
    localFlammable.value = false;
    localPerishable.value = false;
    localSearch.value = '';
    localCategory.value = null;

    updateFilters();
    emit('clear');
    showFilters.value = false;
}
</script>
