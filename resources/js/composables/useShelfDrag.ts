// useShelfDrag.js
import { ref, computed, inject } from 'vue';

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
    // Injetar as seções do contexto
    const localSections = inject('sections', ref([]));

    // Estado para rastrear a operação de arrasto
    const draggingBetweenSections = ref(false);
    const targetSectionId = ref<any>(null);
    const initialMouseX = ref(0);
    const currentMouseX = ref(0);
    const isDragHandle = ref(false);

    // Referência para o elemento da prateleira
    const shelfElement = ref<HTMLElement | null>(null);

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

        // Aplicar classe de arrastrando
        if (shelfElement.value) {
            shelfElement.value.classList.add('shelf-dragging-active');

            // Se estiver usando o ícone Move, adicionar classe específica
            if (isDragHandle.value) {
                shelfElement.value.classList.add('handle-dragging');
            }
        }

        // Configura handlers para movimento e finalização do arrasto
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);

        // Adicionar classe no body para mudar cursor global
        document.body.classList.add('shelf-dragging-in-progress');

        // Disparar evento de diagnóstico
        dispatchDragEvent('shelf-drag-start', {
            shelfId: props.shelf.id,
            sectionId: props.shelf.section.id,
            isDragHandle: isDragHandle.value
        });
    };

    // Função auxiliar para disparar eventos de diagnóstico
    const dispatchDragEvent = (eventName, detail) => {
        window.dispatchEvent(new CustomEvent(eventName, { detail }));
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
            // Estamos no modo de transferência entre seções
            // Verificar se está sobre uma seção
            const elementsUnderCursor = document.elementsFromPoint(event.clientX, event.clientY);

            // Limpar destaques visuais anteriores
            document.querySelectorAll('.section-item').forEach(section => {
                section.classList.remove('potential-drop-target');
            });

            // Encontrar o elemento da seção sob o cursor
            const sectionElement = elementsUnderCursor.find(el =>
                el.classList.contains('section-item')
            );

            if (sectionElement) {
                const sectionId = (sectionElement as HTMLElement).dataset.sectionId;

                // Se for uma seção diferente da atual
                if (sectionId && sectionId !== props.shelf.section.id) {
                    targetSectionId.value = sectionId;
                    sectionElement.classList.add('potential-drop-target');

                    // Disparar evento de diagnóstico
                    dispatchDragEvent('shelf-drag-move', {
                        position: event.clientY,
                        targetSection: sectionId,
                        deltaX
                    });
                } else {
                    targetSectionId.value = null;
                }
            } else {
                targetSectionId.value = null;
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

        // Remover classes de estilo
        document.body.classList.remove('shelf-dragging-in-progress');

        if (shelfElement.value) {
            shelfElement.value.classList.remove('shelf-dragging-active');
            shelfElement.value.classList.remove('handle-dragging');
            // Resetar qualquer transformação aplicada
            shelfElement.value.style.transform = '';
        }

        // Remover destaque de seções
        document.querySelectorAll('.section-item').forEach(section => {
            section.classList.remove('potential-drop-target');
        });

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
                emit('update:shelf', { ...props.shelf, position: shelfPosition.value });
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

    // Manipuladores para eventos de toque
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
        initialMouseX.value = touch.clientX;
        currentMouseX.value = touch.clientX;
        originalPosition.value = shelfPosition.value;
        isDragging.value = true;

        // Guardar referência ao elemento da prateleira
        shelfElement.value = event.target.closest('.shelf-container');

        if (shelfElement.value) {
            shelfElement.value.classList.add('shelf-dragging-active');

            if (isDragHandle.value) {
                shelfElement.value.classList.add('handle-dragging');
            }
        }

        // Adicionar listeners de evento
        document.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('touchend', onTouchEnd);

        document.body.classList.add('shelf-dragging-in-progress');
    };

    // Manipulador para movimento de toque durante arrasto
    const onTouchMove = (event) => {
        if (!isDragging.value) return;

        // Prevenir comportamento padrão (como scroll)
        event.preventDefault();

        // Obter posição atual do toque
        const touch = event.touches[0];
        currentMouseX.value = touch.clientX;

        // Calcular deslocamento horizontal
        const deltaX = currentMouseX.value - initialMouseX.value;

        // Se está usando o ícone Move para arrastar entre seções
        if (isDragHandle.value || draggingBetweenSections.value) {
            // Verificar se está sobre uma seção
            const elementsUnderTouch = document.elementsFromPoint(touch.clientX, touch.clientY);

            // Limpar destaques visuais anteriores
            document.querySelectorAll('.section-item').forEach(section => {
                section.classList.remove('potential-drop-target');
            });

            // Encontrar o elemento da seção sob o toque
            const sectionElement = elementsUnderTouch.find(el =>
                el.classList.contains('section-item')
            );

            if (sectionElement) {
                const sectionId = (sectionElement as HTMLElement).dataset.sectionId;

                if (sectionId && sectionId !== props.shelf.section.id) {
                    targetSectionId.value = sectionId;
                    sectionElement.classList.add('potential-drop-target');
                } else {
                    targetSectionId.value = null;
                }
            } else {
                targetSectionId.value = null;
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
        }
    };

    // Manipulador para finalização do toque
    const onTouchEnd = (event) => {
        // Remover listeners
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);

        // Remover classes de estilo
        document.body.classList.remove('shelf-dragging-in-progress');

        if (shelfElement.value) {
            shelfElement.value.classList.remove('shelf-dragging-active');
            shelfElement.value.classList.remove('handle-dragging');
            // Resetar transformação
            shelfElement.value.style.transform = '';
        }

        // Remover destaque de seções
        document.querySelectorAll('.section-item').forEach(section => {
            section.classList.remove('potential-drop-target');
        });

        // Verificar se havia uma seção alvo para transferência
        if ((isDragHandle.value || draggingBetweenSections.value) && targetSectionId.value) {
            // Emitir evento de transferência
            emit('transfer-shelf', {
                shelf: props.shelf,
                fromSectionId: props.shelf.section.id,
                toSectionId: targetSectionId.value,
                position: shelfPosition.value
            });
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
        if (!data) return;

        try {
            const draggedItem = JSON.parse(data);

            if (draggedItem.type === 'product') {
                // Lógica existente para produtos
            }
        } catch (err) {
            console.error('Error processing drop:', err);
        }
    };

    return {
        onMouseDown,
        onTouchStart,
        onDragOver,
        onDragLeave,
        onDrop,
    };
}