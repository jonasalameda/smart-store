document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("customerForm");
    if (!form) {
        return;
    }

    /**
     * Function to trigger hardware indication (LED/Buzzer)
     * @param {string} status - 'success' or 'error'
     */
    function apiBaseFromForm() {
        if (typeof window.APP_API_BASE === 'string' && window.APP_API_BASE !== '') {
            return window.APP_API_BASE.replace(/\/$/, '');
        }
        var action = form.getAttribute('action') || '';
        try {
            var u = new URL(action, window.location.origin);
            var parts = u.pathname.split('/').filter(Boolean);
            if (parts.length > 0) {
                return '/' + parts[0];
            }
        } catch (e) { /* ignore */ }
        var m = action.match(/^(\/[^/]+)/);
        return m ? m[1] : '';
    }

    async function triggerHardwareIndication(status) {
        try {
            var base = apiBaseFromForm();
            if (!base) {
                return;
            }
            const response = await fetch(base + '/api/hardware/indicate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status: status })
            });

            const data = await response.json();
            
            if (!response.ok) {
                console.error('Hardware indication failed:', data.message);
            } else {
                console.log('Hardware indication activated:', data.message);
            }
        } catch (error) {
            console.error('Error calling hardware API:', error);
            // Don't block the UI if hardware fails
        }
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault(); 

        const firstNameEl = document.getElementById("first_name");
        const lastNameEl = document.getElementById("last_name");
        const phoneEl = document.getElementById("phone");
        const addressEl = document.getElementById("address");
        const emailEl = document.getElementById("email");
        if (!firstNameEl || !phoneEl || !emailEl) {
            form.submit();
            return;
        }

        const first_name = firstNameEl.value.trim();
        const last_name = lastNameEl ? lastNameEl.value.trim() : "";
        const phone = phoneEl.value.trim();
        const address = addressEl ? addressEl.value.trim() : "";
        const email = emailEl.value.trim();

        // Validate form data   //-Emmanuel we dont need this because i added required, meaning any of the field cannot emptied 
        
        
        //Mariam: I will Remove 'required' since we want server-side validation to trigger the LEDs, not in-browser validation


        // if (!first_name || !last_name || !phone || !address || !email) {
        //     event.preventDefault();
        //     alert("Please fill in all required fields!");
        //     triggerHardwareIndication('error');
        //     return;
        // }

        // try {
        //     // TODO: Replace this with actual database API call when implementing database saving
        //     // Example:
        //     // const response = await fetch('/api/customers', {
        //     //     method: 'POST',
        //     //     headers: { 'Content-Type': 'application/json' },
        //     //     body: JSON.stringify({ fname, lname, phone, address, email })
        //     // });
        //     // if (!response.ok) {
        //     //     throw new Error('Failed to save customer');
        //     // }

        //     // For now, just add to table (existing notification logic)
        //     let table = document.getElementById("customerTable");

        //     let row = document.createElement("tr");

        //     row.innerHTML = `
        //         <td>${fname}</td>
        //         <td>${lname}</td>
        //         <td>${phone}</td>
        //         <td>${address}</td>
        //         <td>${email}</td>
        //     `;

        //     table.appendChild(row);

        //     alert("Customer added successfully!");

        //     // Trigger success indication (blue LED)
        //     triggerHardwareIndication('success');

        //     form.reset();
        // } catch (error) {
        //     // Handle errors (e.g., database save failure)
        //     console.error('Error saving customer:', error);
        //     alert("Error: Failed to save customer. Please try again.");
        //     // Trigger error indication (red LED + buzzer)
        //     triggerHardwareIndication('error');
        // }

        const singleNameRegex = /^[A-Za-z]{2,50}$/;
        const fullNameRegex = /^[\p{L}][\p{L}\s'.-]{1,99}$/u;
        const phoneDigits = phone.replace(/\D/g, '');
        const phoneRegex = /^\d{10}$/;
        const addressRegex = /^.{0,200}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!first_name || !phone || !email || (lastNameEl && !last_name)) {
            await triggerHardwareIndication('error');
            alert("Please fill in all required fields!");
            return;
        }

        if (lastNameEl) {
            if (!singleNameRegex.test(first_name) || !singleNameRegex.test(last_name)) {
                await triggerHardwareIndication('error');
                alert("Names must be 2-50 letters only.");
                return;
            }
        } else {
            if (!fullNameRegex.test(first_name)) {
                await triggerHardwareIndication('error');
                alert("Please enter a valid name (2-100 characters).");
                return;
            }
        }

        if (!phoneRegex.test(phoneDigits)) {
            await triggerHardwareIndication('error');
            alert("Phone must be at least 10 digits.");
            return;
        }

        if (!addressRegex.test(address)) {
            await triggerHardwareIndication('error');
            alert("Address must be at most 200 characters.");
            return;
        }

        if (!emailRegex.test(email)) {
            await triggerHardwareIndication('error');
            alert("Email must have the format abc@example.com");
            return;
        }
        form.submit();
    });
});