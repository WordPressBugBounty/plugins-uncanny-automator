class AutomatorGoogleCalendarSettings {
    constructor() {
        // Retrieve calendars on load
        this.retrieveCalendars();
    }

    /**
     * Retrieve the Facebook Pages linked
     * 
     * @return {undefined}
     */
    retrieveCalendars() {
        // Set the loading status of the button
        this.$preloaderButton.setAttribute('loading', '');

        fetch( UncannyAutomatorBackend.ajax.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache'
            },
            body: new URLSearchParams({
                action: 'automator_google_calendar_list_calendars',
                nonce: UncannyAutomatorBackend.ajax.nonce,
                timestamp: +new Date()
            } )
        })
            .then( ( response ) => response.json() )
            .then( response => {
                // Check if there are pages
                if ( Array.isArray( response.data ) && response.data.length > 0 ) {
                    // Set pages
                    this.createList(response.data);
                } else {

                    if (response.data) {
                        if ( typeof response.data.error !== 'undefined' && response.data.error !== '' ) {
                            this.setError(response.data.error);
                        }
                    }

                    if (response.error) {
                        if ( typeof response.error !== 'undefined' && response.error !== '' ) {
                            this.setError(response.error);
                        }
                    }
                }
            } )
            .catch( error => {
                console.error( error );
            } )
            .finally( () => {
                if ( this.$preloaderButton ) {
                    // Remove the loading animation.
                    this.$preloaderButton.remove();
                }
            } );
    }

    /**
     * Creates the list with the Facebook pages
     * 
     * @param  {Array}  pages An array with the Facebook pages
     * @return {undefined}       
     */
    createList(calendars = []) {
        // Remove the current content
        this.$listWrapper.innerHTML = '';
        // Iterate list
        calendars.forEach((calendar) => {
            // Append page
            this.$listWrapper.insertAdjacentHTML(
                'beforeEnd',
                `
                    <div class="uap-google-calendar uap-spacing-top">

                        <span class="uap-google-calendar__marker uap-spacing-right" style="background-color:${calendar.backgroundColor};"></span>
                        
                        <strong data-calendar-id="${calendar.id}">
                            ${calendar.summary}
                        </strong>

                        <div class="uap-google-calendar__meta">
                            ${calendar.description}
                        </div>
                        
                        <div class="uap-google-calendar__meta">
                            ${calendar.timeZone}
                        </div>
                    </div>
                `
            );
        });
    }

    /**
     * Displays an error
     * 
     * @param {String} error The error
     */
    setError(error = '') {

        // Check if there is an error defined
        if ( error ) {
            this.$errorWrapper.innerHTML = error;
            this.$errorWrapper.style.display = 'block';
        } else {
            // Remove the current error, and hide the notice
            this.$errorWrapper.innerHTML = '';
            this.$errorWrapper.style.display = 'none';
        }
    }

    /**
     * Returns the pre-loader button
     * 
     * @return {Node} The button
     */
    get $preloaderButton() {
        return document.getElementById('google-calendar-preloader');
    }

    /**
     * Returns the list wrapper
     * 
     * @return {Node} The wrapper
     */
    get $listWrapper() {
        return document.getElementById('google-calendar-list');
    }

    /**
     * Returns the wrapper used to show errors
     * 
     * @return {Node} The wrapper
     */
    get $errorWrapper() {
        return document.getElementById('google-calendar-errors');
    }
}

// Create instance
new AutomatorGoogleCalendarSettings();
