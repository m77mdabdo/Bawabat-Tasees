import Alpine from 'alpinejs';

/**
 * Motion controls for the parts of the public site that move on their own.
 *
 * WCAG 2.2.2 (Pause, Stop, Hide) requires a mechanism to pause anything that
 * moves automatically for more than five seconds. Three things here did:
 * the homepage hero video, the login background video and the testimonial
 * carousel. None of them offered one, and none honoured
 * prefers-reduced-motion — which the marquee already did, so the site was
 * inconsistent with itself as well as with the guideline.
 *
 * Two rules, applied the same way to all three:
 *
 *   1. prefers-reduced-motion means it does not start.
 *   2. There is always a visible control to stop and restart it, and the
 *      control works whether or not rule 1 kicked in.
 *
 * Registered here rather than inline in Blade because the video behaviour is
 * needed by two different layouts, and because "pause it when the OS says
 * so" is logic, not markup.
 */

export const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * A decorative, muted, looping background video.
 *
 * The element keeps its `autoplay` attribute so the design still works with
 * JavaScript off. When reduced motion is set we pause on init instead —
 * `preload="metadata"` means little or nothing has been decoded by then, so
 * in practice this stops it before it moves rather than after.
 *
 * `playing` follows the element's own play/pause events rather than being
 * set optimistically: browsers refuse autoplay in low-power mode and on some
 * mobile defaults, and a toggle button that claims to be "playing" while the
 * video sits still is worse than no button.
 */
Alpine.data('backgroundVideo', () => ({
    playing: false,

    init() {
        const video = this.$refs.video;

        if (! video) {
            return;
        }

        video.addEventListener('play', () => (this.playing = true));
        video.addEventListener('pause', () => (this.playing = false));

        if (prefersReducedMotion()) {
            video.pause();
        }

        this.playing = ! video.paused;
    },

    toggle() {
        const video = this.$refs.video;

        if (! video) {
            return;
        }

        if (video.paused) {
            video.play();
        } else {
            video.pause();
        }
    },
}));

/**
 * A slideshow that advances on a timer.
 *
 * `stopped` is the user's explicit intent and is deliberately separate from
 * whether the timer is currently running: hovering suspends the timer, but
 * moving the pointer away must not silently restart something the visitor
 * chose to stop.
 */
Alpine.data('autoAdvance', (count, interval = 6000) => ({
    active: 0,
    count,
    stopped: false,
    timer: null,

    init() {
        this.stopped = prefersReducedMotion();
        this.resume();
    },

    resume() {
        if (this.stopped || this.timer !== null) {
            return;
        }

        this.timer = setInterval(() => this.next(), interval);
    },

    suspend() {
        clearInterval(this.timer);
        this.timer = null;
    },

    toggle() {
        this.stopped = ! this.stopped;

        if (this.stopped) {
            this.suspend();
        } else {
            this.resume();
        }
    },

    next() {
        this.active = (this.active + 1) % this.count;
    },

    // Alpine calls this when the component is removed, so a slideshow on a
    // page fragment cannot leave a timer running against a detached DOM.
    destroy() {
        this.suspend();
    },
}));
