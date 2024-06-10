window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });

window.$ = window.jQuery = require('jquery')
window.Swal = require('sweetalert2')

import "admin-lte/plugins/datatables/jquery.dataTables.min.js"
import "admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"
import "admin-lte/plugins/datatables-responsive/js/dataTables.responsive.min.js"
import "admin-lte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"
import "admin-lte/plugins/datatables-buttons/js/dataTables.buttons.min.js"
import "admin-lte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"
import "admin-lte/plugins/jszip/jszip.min.js"
import "admin-lte/plugins/pdfmake/pdfmake.min.js"
import "admin-lte/plugins/pdfmake/vfs_fonts.js"
import "admin-lte/plugins/datatables-buttons/js/buttons.html5.min.js"
import "admin-lte/plugins/datatables-buttons/js/buttons.print.min.js"
import "admin-lte/plugins/datatables-buttons/js/buttons.colVis.min.js"


// import "admin-lte/plugins/jquery/jquery"
import "admin-lte/plugins/daterangepicker/daterangepicker"
import "admin-lte/plugins/bootstrap/js/bootstrap.bundle"
import "admin-lte/dist/js/adminlte"