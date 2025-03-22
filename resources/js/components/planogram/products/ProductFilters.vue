<template>
    <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-4">
        <!-- Search bar -->
        <Input v-model="localFilters.search" placeholder="Buscar produtos..." class="w-full">
            <template #prefix>
                <Search class="w-4 h-4" />
            </template>
        </Input>

        <!-- Collapsible filter section -->
        <Collapsible v-model:open="showFilters" class="mt-4">
            <CollapsibleTrigger asChild>
                <Button variant="outline" class="w-full flex justify-between items-center">
                    <div class="flex items-center">
                        <SlidersHorizontal class="h-4 w-4 mr-2" />
                        <span>Filtros</span>

                        <!-- Badge showing number of active filters -->
                        <Badge v-if="activeFiltersCount > 0" class="ml-2" variant="secondary">
                            {{ activeFiltersCount }}
                        </Badge>
                    </div>
                    <ChevronDown class="h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-180': showFilters }" />
                </Button>
            </CollapsibleTrigger>

            <CollapsibleContent class="mt-4 space-y-4 p-4">
                <!-- Category filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>

                    <Select v-model="localFilters.category" class="w-full">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecionar categoria" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in categoryOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Attribute filters -->
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Atributos</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localFilters.hangable" id="hangable" />
                            <label for="hangable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Pendurável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localFilters.stackable" id="stackable" />
                            <label for="stackable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Empilhável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localFilters.flammable" id="flammable" />
                            <label for="flammable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Inflamável</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model:checked="localFilters.perishable" id="perishable" />
                            <label for="perishable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Perecível</label>
                        </div>
                    </div>
                </div>

                <!-- Filter actions -->
                <div class="flex justify-end">
                    <Button variant="outline" size="sm" @click="clearFilters">Limpar filtros</Button>
                </div>
            </CollapsibleContent>
        </Collapsible>
    </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, SlidersHorizontal, ChevronDown } from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

const props = defineProps({
    filters: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        default: () => []
    },
    activeFiltersCount: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['update:filters', 'clear']);

const showFilters = ref(false);

// Use computed properties for two-way binding
const localFilters = computed({
    get() {
        return props.filters;
    },
    set(newValue) {
        emit('update:filters', newValue);
    }
});

// Category options for the select component
const categoryOptions = computed(() => {
    return [
        { label: 'Todas as categorias', value: null },
        ...props.categories.map(category => ({
            label: category.name,
            value: category.id
        }))
    ];
});

// Clear all filters
function clearFilters() {
    emit('clear');
    showFilters.value = false;
}
</script>