define([], function() {
    let activeShellId = 'shell_1';

    function setActiveShell(shellId) {
        // Remove active class from all shells.
        document.querySelectorAll('.duallist-shell').forEach(shell => {
            shell.classList.remove('active-shell');
        });
        
        // Add active class to the clicked shell.
        const shellContainer = document.getElementById(shellId).parentElement;
        shellContainer.classList.add('active-shell');
        
        // Update active shell ID.
        activeShellId = shellId;
    }
    
    function moveToShell() {
        const available = document.getElementById('available_sections');
        const target = document.getElementById(activeShellId);
        
        if (available && target) {
            // Convert to array to safely iterate while modifying.
            const selectedOptions = Array.from(available.options)
                .filter(option => option.selected);
            
            // Move each selected option to the active shell.
            selectedOptions.forEach(option => {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                target.appendChild(newOption);
                
                // Remove the original from available.
                available.removeChild(option);
            });
        }
    }

    function moveBackToAvailable() {
        const available = document.getElementById('available_sections');
        
        // Process all shell selects.
        document.querySelectorAll('select[id^="shell_"]').forEach(shellSelect => {
            // Convert to array to safely iterate while modifying.
            const selectedOptions = Array.from(shellSelect.options)
                .filter(option => option.selected);
            
            // Move each selected option back to available.
            selectedOptions.forEach(option => {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                available.appendChild(newOption);
                
                // Remove the original from the shell.
                shellSelect.removeChild(option);
            });
        });
    }

    function init() {
        // Make the functions available globally.
        window.moveToShell = moveToShell;
        window.moveBackToAvailable = moveBackToAvailable;
        window.setActiveShell = setActiveShell;
        
        // Add click event to shells to set active shell.
        document.querySelectorAll('.duallist-shell').forEach(shell => {
            const selectElement = shell.querySelector('select');
            if (selectElement) {
                shell.addEventListener('click', function() {
                    setActiveShell(selectElement.id);
                });
                
                // Make the select element forward click events to its parent.
                selectElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    setActiveShell(this.id);
                });
            }
        });
        
        // Set the first shell as active by default.
        setActiveShell('shell_1');

        const form = document.querySelector('form.mform');
        // Ensure all options in shells are selected when submitting.
        form.addEventListener('submit', function(event) {
            console.log('Form is submitting');
            document.querySelectorAll('select[name^="shell_"]').forEach(function(select) {
                Array.from(select.options).forEach(function(option) {
                    option.selected = true;
                });
            });
        });
    }

    return {
        init: init
    };
});
