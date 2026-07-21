import './bootstrap';
import Alpine from 'alpinejs';
import './theme';
import './realtime-sync';
import './dashboard';
import './timer';
import './notifications';
import './admin-actions';
import './workflow-actions';

window.Alpine = Alpine;
Alpine.start();

// The old ajax-polling.js is replaced by realtime-sync.js
// Init is now handled in app.blade.php DOMContentLoaded via RealtimeSync
