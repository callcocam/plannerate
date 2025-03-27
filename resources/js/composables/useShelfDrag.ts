// useShelfDrag.js
import { ref, inject } from 'vue';
import useShelfTouch from './useShelfTouch';
import useShelfDragCommon from './useShelfDragCommon';

export default function useShelfDrag({
    props,
    shelfPosition,
    isDragging,
    startY,
    originalPosition,
    scaleFactor,
    emit,
    minUpatePosition,
    alignShelf,
    openProductSettings,
    isDropTarget,
    dragLeaveTimeout,
}) { 

    // Estado para rastrear a operação de arrasto
    const draggingBetweenSections = ref(false);
    const targetSectionId = ref(null);
    const initialMouseX = ref(0);
    const currentMouseX = ref(0);
    const isDragHandle = ref(false);

    // Referência para o elemento da prateleira
    const shelfElement = ref<any>(null);

    // Obter funções compartilhadas
    const {
        dispatchDragEvent,
        addPotentialDropTargetClass,
        removePotentialDropTargetClasses,
        prepareElementForDrag,
        cleanupElementAfterDrag,
        findSectionElementUnderPoint
    } = useShelfDragCommon();

    // Manipulador para início do arrasto com mouse na alça (Move icon)
    const onMouseDown = (event) => {
        // Verificar se o arrasto iniciou no ícone Move (shelf-handle)
        isDragHandle.value = !!event.target.closest('.shelf-handle');

        // Se está arrastando a partir do ícone Move ou se está arrastando a prateleira normalmente
        if (isDragHandle.value) {
            // Arrasto para transferência entre seções (pelo ícone Move)
            console.log('Iniciando arrasto com alça para transferência entre seções');
            draggingBetweenSections.value = true;
        } else {
            // Se clicar em outros elementos específicos, não inicia o arrasto
            if (
                (openProductSettings && openProductSettings.value) ||
                event.target.closest('.segment') ||
                event.button !== 0
            ) {
                return;
            }

            // Arrasto normal para reposicionamento vertical
            draggingBetweenSections.value = false;
        }

        // Evita propagação e comportamento padrão
        event.stopPropagation();
        event.preventDefault();

        // Configura o início do arrasto
        startY.value = event.clientY;
        initialMouseX.value = event.clientX;
        currentMouseX.value = event.clientX;
        originalPosition.value = shelfPosition.value;
        isDragging.value = true;

        // Guardar referência ao elemento da prateleira
        shelfElement.value = event.target.closest('.shelf-container');

        // Preparar elemento para arrastar
        prepareElementForDrag(shelfElement.value, isDragHandle.value);

        // Configura handlers para movimento e finalização do arrasto
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);

        // Disparar evento de diagnóstico
        dispatchDragEvent('shelf-drag-start', {
            shelfId: props.shelf.id,
            sectionId: props.shelf.section.id,
            isDragHandle: isDragHandle.value
        });
    };

    // Manipulador para movimento do mouse durante arrasto
    const onMouseMove = (event) => {
        if (!isDragging.value) return;

        // Atualizar a posição atual do mouse
        currentMouseX.value = event.clientX;

        // Calcular deslocamento horizontal
        const deltaX = currentMouseX.value - initialMouseX.value;

        // Se está usando o ícone Move para arrastar entre seções
        if (isDragHandle.value || draggingBetweenSections.value) {
            // Limpar destaques visuais anteriores
            removePotentialDropTargetClasses();

            // Encontrar a seção sob o cursor
            const { sectionElement, targetSectionId: newTargetId } = findSectionElementUnderPoint(
                event.clientX, 
                event.clientY, 
                props.shelf.section.id
            );

            // Atualizar a seção alvo
            targetSectionId.value = newTargetId;

            if (sectionElement && newTargetId) {
                // Adicionar destaque visual
                addPotentialDropTargetClass(sectionElement);

                // Disparar evento de diagnóstico
                dispatchDragEvent('shelf-drag-move', {
                    position: event.clientY,
                    targetSection: newTargetId,
                    deltaX
                });
            }

            // Aplicar efeito visual ao arrastar (mover com o mouse)
            if (shelfElement.value) {
                // Aplicar transformação para seguir o mouse (efeito de "arrasto")
                shelfElement.value.style.transform = `translate(${deltaX}px, 0)`;
            }
        } else {
            // Arrasto normal vertical
            // Calcular o deslocamento vertical
            const deltaY = event.clientY - startY.value;
            const deltaPosition = deltaY / scaleFactor.value;

            // Atualizar a posição considerando a direção da prateleira
            if (props.shelfDirection === 'top') {
                shelfPosition.value = originalPosition.value + deltaPosition;
            } else {
                shelfPosition.value = originalPosition.value - deltaPosition;
            }

            // Disparar evento de diagnóstico
            dispatchDragEvent('shelf-drag-move', {
                position: shelfPosition.value
            });
        }
    };

    // Manipulador para finalização do arrasto
    const onMouseUp = (event) => {
        // Remover listeners
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);

        // Limpar elementos e estados visuais
        cleanupElementAfterDrag(shelfElement.value);

        // Verificar se havia uma seção alvo para transferência
        let transferred = false;

        if ((isDragHandle.value || draggingBetweenSections.value) && targetSectionId.value) {
            console.log('Transferindo prateleira de', props.shelf.section.id, 'para', targetSectionId.value);

            // Emitir evento de transferência
            emit('transfer-shelf', {
                shelf: props.shelf,
                fromSectionId: props.shelf.section.id,
                toSectionId: targetSectionId.value,
                position: shelfPosition.value // Manter a mesma posição vertical na nova seção
            });

            transferred = true;
        } else if (!isDragHandle.value && isDragging.value) {
            // Se foi apenas arrasto vertical normal
            if (Math.abs(minUpatePosition.value - shelfPosition.value) > 1) {
                minUpatePosition.value = shelfPosition.value;
                emit('update:shelf', { ...props.shelf, position: shelfPosition.value, preserveState: true });
            }

            // Alinhar a prateleira ao furo mais próximo após um delay
            setTimeout(() => {
                alignShelf();
            }, 200);
        }

        // Disparar evento de diagnóstico
        dispatchDragEvent('shelf-drag-end', {
            transferred,
            targetSection: targetSectionId.value
        });

        // Resetar todos os estados
        isDragging.value = false;
        draggingBetweenSections.value = false;
        targetSectionId.value = null;
        isDragHandle.value = false;
        shelfElement.value = null;
    };

    // Manipuladores para drag & drop nativo (para produtos)
    const onDragOver = (event) => {
        event.preventDefault();
        isDropTarget.value = true;

        if (dragLeaveTimeout.value) {
            clearTimeout(dragLeaveTimeout.value);
            dragLeaveTimeout.value = null;
        }
    };

    const onDragLeave = (event) => {
        dragLeaveTimeout.value = setTimeout(() => {
            isDropTarget.value = false;
        }, 50);
    };

    const onDrop = (event) => {
        event.preventDefault();
        isDropTarget.value = false;

        const data = event.dataTransfer.getData('application/json');
        console.log('Data from drop:', data);
        if (!data) return;

        try {
            const product = JSON.parse(data);
            // Create a new segment with all necessary properties
            const newSegment = {
                id: `segment-${Date.now()}`,
                width: parseInt(props.shelf.section?.width),
                ordering: (props.shelf.segments?.length || 0) + 1,
                quantity: 1,
                spacing: 0,
                position: 0,
                preserveState: false,
                status: 'published',
                // Create layer with product information
                layer: {
                    id: `layer-${Date.now()}`,
                    product_id: product.id,
                    product_name: product.name,
                    product_image: product.image,
                    height: product.height,
                    spacing: 0,
                    quantity: 1,
                    status: 'published',
                }
            };

            // Update the shelf with the new segment
            const updatedShelf = {
                ...props.shelf,
                segment: newSegment,
            };

            // Emite o evento para atualizar a prateleira
            emit('update:shelf', updatedShelf);

            // Emite o evento selectShelf quando um produto é colocado
            emit('selectShelf', props.shelf);

        } catch (err) {
            console.error('Error processing drop:', err);
        }
    };

    // Inicializa o composable de toque com as dependências necessárias
    const touchHandlers = useShelfTouch({
        props,
        shelfPosition,
        isDragging,
        startY,
        originalPosition,
        scaleFactor,
        emit,
        minUpatePosition,
        alignShelf,
        openProductSettings,
        isDropTarget,
        dispatchDragEvent,
        addPotentialDropTargetClass,
        removePotentialDropTargetClasses,
        findSectionElementUnderPoint,
        prepareElementForDrag,
        cleanupElementAfterDrag
    });

    return {
        onMouseDown,
        onTouchStart: touchHandlers.onTouchStart,
        onDragOver,
        onDragLeave,
        onDrop,
    };
}