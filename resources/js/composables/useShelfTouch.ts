// useShelfTouch.js
import { ref } from 'vue';

export default function useShelfTouch({
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
    // Funções compartilhadas
    dispatchDragEvent,
    addPotentialDropTargetClass,
    removePotentialDropTargetClasses,
    findSectionElementUnderPoint,
    prepareElementForDrag,
    cleanupElementAfterDrag
}) {
    // Estado para rastrear a operação de arrasto via toque
    const draggingBetweenSections = ref(false);
    const targetSectionId = ref(null);
    const initialTouchX = ref(0);
    const currentTouchX = ref(0);
    const isDragHandle = ref(false);

    // Referência para o elemento da prateleira
    const shelfElement: any = ref(null);

    // Manipulador para início do toque
    const onTouchStart = (event) => {
        // Verificar se o toque iniciou no ícone Move
        isDragHandle.value = !!event.target.closest('.shelf-handle');

        if (isDragHandle.value) {
            // Arrasto para transferência entre seções
            draggingBetweenSections.value = true;
        } else {
            // Se tocar em outros elementos específicos, não inicia o arrasto
            if (
                (openProductSettings && openProductSettings.value) ||
                event.target.closest('.segment')
            ) {
                return;
            }

            // Arrasto normal
            draggingBetweenSections.value = false;
        }

        // Prevenir comportamentos padrão do navegador
        event.preventDefault();
        event.stopPropagation();

        // Configurar início do arrasto
        const touch = event.touches[0];
        startY.value = touch.clientY;
        initialTouchX.value = touch.clientX;
        currentTouchX.value = touch.clientX;
        originalPosition.value = shelfPosition.value;
        isDragging.value = true;

        // Guardar referência ao elemento da prateleira
        shelfElement.value = event.target.closest('.shelf-container');

        // Preparar elemento para arrastar
        prepareElementForDrag(shelfElement.value, isDragHandle.value);

        // Adicionar listeners de evento
        document.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('touchend', onTouchEnd);

        // Disparar evento de diagnóstico
        dispatchDragEvent('shelf-drag-start', {
            shelfId: props.shelf.id,
            sectionId: props.shelf.section.id,
            isDragHandle: isDragHandle.value,
            isTouchEvent: true
        });
    };

    // Manipulador para movimento de toque durante arrasto
    const onTouchMove = (event) => {
        if (!isDragging.value) return;

        // Prevenir comportamento padrão (como scroll)
        event.preventDefault();

        // Obter posição atual do toque
        const touch = event.touches[0];
        currentTouchX.value = touch.clientX;

        // Calcular deslocamento horizontal
        const deltaX = currentTouchX.value - initialTouchX.value;

        // Se está usando o ícone Move para arrastar entre seções
        if (isDragHandle.value || draggingBetweenSections.value) {
            // Limpar destaques visuais anteriores
            removePotentialDropTargetClasses();

            // Encontrar a seção sob o toque
            const { sectionElement, targetSectionId: newTargetId } = findSectionElementUnderPoint(
                touch.clientX,
                touch.clientY,
                props.shelf.section.id
            );

            // Atualizar a seção alvo
            targetSectionId.value = newTargetId;

            if (sectionElement && newTargetId) {
                // Adicionar destaque visual
                addPotentialDropTargetClass(sectionElement);

                // Disparar evento de diagnóstico
                dispatchDragEvent('shelf-drag-move', {
                    position: touch.clientY,
                    targetSection: newTargetId,
                    deltaX,
                    isTouchEvent: true
                });
            }

            // Aplicar efeito visual ao arrastar
            if (shelfElement.value) {
                shelfElement.value.style.transform = `translate(${deltaX}px, 0)`;
            }
        } else {
            // Arrasto vertical normal
            const deltaY = touch.clientY - startY.value;
            const deltaPosition = deltaY / scaleFactor.value;

            if (props.shelfDirection === 'top') {
                shelfPosition.value = originalPosition.value + deltaPosition;
            } else {
                shelfPosition.value = originalPosition.value - deltaPosition;
            }

            // Disparar evento de diagnóstico
            dispatchDragEvent('shelf-drag-move', {
                position: shelfPosition.value,
                isTouchEvent: true
            });
        }
    };

    // Manipulador para finalização do toque
    const onTouchEnd = (event) => {
        // Remover listeners
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);

        // Limpar elementos e estados visuais
        cleanupElementAfterDrag(shelfElement.value);

        // Verificar se havia uma seção alvo para transferência
        let transferred = false;

        if ((isDragHandle.value || draggingBetweenSections.value) && targetSectionId.value) {
            console.log('Transferindo prateleira (touch) de', props.shelf.section.id, 'para', targetSectionId.value);

            // Emitir evento de transferência
            emit('transfer-shelf', {
                shelf: props.shelf,
                fromSectionId: props.shelf.section.id,
                toSectionId: targetSectionId.value,
                position: shelfPosition.value
            });

            transferred = true;
        } else if (!isDragHandle.value && isDragging.value) {
            // Se foi apenas arrasto vertical normal
            if (Math.abs(minUpatePosition.value - shelfPosition.value) > 1) {
                minUpatePosition.value = shelfPosition.value;
                emit('update:shelf', { ...props.shelf, position: shelfPosition.value });
            }

            setTimeout(() => {
                alignShelf();
            }, 200);
        }

        // Disparar evento de diagnóstico
        dispatchDragEvent('shelf-drag-end', {
            transferred,
            targetSection: targetSectionId.value,
            isTouchEvent: true
        });

        // Resetar todos os estados
        isDragging.value = false;
        draggingBetweenSections.value = false;
        targetSectionId.value = null;
        isDragHandle.value = false;
        shelfElement.value = null;
    };

    return {
        onTouchStart
    };
}