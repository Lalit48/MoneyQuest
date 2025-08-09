document.addEventListener('DOMContentLoaded', () => {
    const cursorDot = document.createElement('div');
    cursorDot.classList.add('custom-cursor', 'cursor-dot');
    document.body.appendChild(cursorDot);

    const cursorOutline = document.createElement('div');
    cursorOutline.classList.add('custom-cursor', 'cursor-outline');
    document.body.appendChild(cursorOutline);

    let mouseX = 0;
    let mouseY = 0;
    let isHovering = false;

    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    const animateCursor = () => {
        cursorDot.style.transform = `translate(${mouseX}px, ${mouseY}px)`;
        cursorOutline.style.transform = `translate(${mouseX - 16}px, ${mouseY - 16}px) ${isHovering ? 'scale(1.5)' : ''}`;
        requestAnimationFrame(animateCursor);
    };

    animateCursor();

    document.querySelectorAll('a, button, input[type="submit"], .hover-target').forEach((el) => {
        el.addEventListener('mouseenter', () => {
            isHovering = true;
            cursorOutline.classList.add('hover');
        });
        el.addEventListener('mouseleave', () => {
            isHovering = false;
            cursorOutline.classList.remove('hover');
        });
    });
});
