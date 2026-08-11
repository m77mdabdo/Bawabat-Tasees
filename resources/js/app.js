import './attribution';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// x-trap, for the two off-canvas drawers. Hand-rolling a focus trap that
// actually handles Shift+Tab wrapping, focus restoration on close and the
// rest of the page's aria-hidden state is a well-known way to ship a subtly
// broken one, so this is the official plugin rather than fifteen lines here.
Alpine.plugin(focus);

// Registers the shared Alpine components (backgroundVideo, autoAdvance).
// Must be imported before Alpine.start(), which the ES module order gives us.
import './motion';

// The cookie consent banner and the time-on-page beacon. Same constraint —
// it registers an Alpine component, so it has to be in before start().
import './visitor-session';

window.Alpine = Alpine;

Alpine.start();
