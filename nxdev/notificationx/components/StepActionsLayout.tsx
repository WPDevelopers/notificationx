import { useEffect } from 'react';

// Talk to Support / Preview sit in each step's heading. On wide screens the
// builder styles move them next to the step bar (see `.nx-steps-with-actions`
// in scss/nx_new/_builder.scss); this measures them so the step bar can make
// room, and picks a step bar layout that fits the space left.
const TABS = [
    ':scope > .wprf-tab-content-wrapper > .wprf-tab-flex > .wprf-tab-contents > .wprf-tab-content',
    ':scope > .wprf-tab-content-wrapper > .wprf-tab-contents > .wprf-tab-content',
].join(', ');

// Width of a step's buttons. Hidden steps are shown for a moment (invisible)
// to measure them, so the step bar keeps one width on every step.
const actionsWidth = (tab: HTMLElement): number => {
    const actions = tab.querySelector(':scope > .wprf-tab-heading-wrapper > div') as HTMLElement | null;
    if (!actions) {
        return 0;
    }
    if (tab.classList.contains('wprf-active')) {
        return Math.ceil(actions.getBoundingClientRect().width);
    }
    const style = tab.getAttribute('style');
    tab.style.cssText += ';display:block !important;visibility:hidden;position:absolute;';
    const width = Math.ceil(actions.getBoundingClientRect().width);
    if (style === null) {
        tab.removeAttribute('style');
    } else {
        tab.setAttribute('style', style);
    }
    return width;
};

const MIN_FORM_WIDTH = 640; // Narrower: keep the buttons in the step heading.

// Step bar layouts, roomiest first: default, tighter, name on the current
// step only, icons only.
const MODES = ['', 'nx-steps-tight', 'nx-steps-compact', 'nx-steps-tiny'];

const StepActionsLayout = () => {
    useEffect(() => {
        const content = document.querySelector('.nx-builder-page .nx-admin-content');
        if (!content) {
            return;
        }
        let frame = 0;

        const update = () => {
            const form = content.querySelector(':scope > .wp-react-form') as HTMLElement | null;
            const steps = form?.querySelector(':scope > .wprf-tab-menu-wrapper') as HTMLElement | null;
            if (!form || !steps) {
                return;
            }
            const tabs = Array.from(form.querySelectorAll(TABS)) as HTMLElement[];
            const active = tabs.find((tab) => tab.classList.contains('wprf-active'));
            const enabled = !!active && actionsWidth(active) > 0 && form.clientWidth >= MIN_FORM_WIDTH;

            form.classList.toggle('nx-steps-with-actions', enabled);
            const width = enabled ? Math.max(...tabs.map(actionsWidth)) : 0;
            form.style.setProperty('--nx-step-actions-width', `${width}px`);

            // Use the roomiest step bar layout whose steps fit on one line.
            const nav = steps.querySelector(':scope .wprf-tab-nav') as HTMLElement | null;
            const fits = () => !nav || nav.scrollWidth <= nav.clientWidth + 1;
            for (let i = 0; i < MODES.length; i++) {
                MODES.forEach((mode) => mode && form.classList.toggle(mode, mode === MODES[i]));
                if (i === MODES.length - 1 || fits()) {
                    break;
                }
            }
            // Name on hover, for steps that show only their icon.
            steps.querySelectorAll(':scope .wprf-tab-nav > .wprf-tab-nav-item').forEach((item: HTMLElement) => {
                const name = item.querySelector('span')?.textContent?.trim();
                if (name && item.title !== name) {
                    item.title = name;
                }
            });
            form.style.setProperty('--nx-steps-height', `${steps.offsetHeight}px`);
        };

        const schedule = () => {
            window.cancelAnimationFrame(frame);
            frame = window.requestAnimationFrame(update);
        };

        // Step changes and the buttons each step shows.
        // Our own class changes on the form are left out, or it would loop.
        const mutations = new MutationObserver((records) => {
            if (records.some((record) => !(record.type === 'attributes' && (record.target as HTMLElement).classList?.contains('wp-react-form')))) {
                schedule();
            }
        });
        mutations.observe(content, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });

        let resize: ResizeObserver | null = null;
        if (typeof ResizeObserver !== 'undefined') {
            resize = new ResizeObserver(schedule);
            resize.observe(content);
        } else {
            window.addEventListener('resize', schedule);
        }
        schedule();

        return () => {
            window.cancelAnimationFrame(frame);
            mutations.disconnect();
            resize?.disconnect();
            window.removeEventListener('resize', schedule);
        };
    }, []);

    return null;
};

export default StepActionsLayout;
