const boxes = document.querySelectorAll('.feature-item');

boxes.forEach(box => {
    box.addEventListener('mouseover', () => {
        box.classList.add('rotate-animation');
    });

    box.addEventListener('mouseout', () => {
        box.classList.remove('rotate-animation');
    });
});