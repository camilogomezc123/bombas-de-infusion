import { toBlob } from 'html-to-image';

window.acvChartImage = {
    async capture(element) {
        const width = Math.ceil(Math.max(element.scrollWidth, element.getBoundingClientRect().width));
        const height = Math.ceil(Math.max(element.scrollHeight, element.getBoundingClientRect().height));
        const longestSide = Math.max(width, height);
        const pixelRatio = Math.min(1.5, 1800 / Math.max(1, longestSide));
        const computedBackground = window.getComputedStyle(element).backgroundColor;
        const backgroundColor = computedBackground === 'rgba(0, 0, 0, 0)'
            ? window.getComputedStyle(document.body).backgroundColor
            : computedBackground;
        const blob = await toBlob(element, {
            backgroundColor,
            cacheBust: false,
            height,
            pixelRatio,
            skipFonts: true,
            width,
            style: {
                cursor: 'default',
                margin: '0',
            },
        });

        if (! blob) {
            throw new Error('No fue posible generar el PNG.');
        }

        return blob;
    },
};
