/*!
* Start Bootstrap - Simple Sidebar v6.0.6
* Copyright 2013-2023 Start Bootstrap
* Licensed under MIT
*/

window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    }

});