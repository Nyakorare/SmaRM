// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
  // Fetch the current team status from the server (check if user is assigned to a team)
  fetch('./system.php')
  .then(response => response.json())
  .then(data => {
    const teamInfo = document.getElementById('team-info');
    const teamSelection = document.getElementById('team-selection');
    const departmentSelect = document.getElementById('department-select');

    if (!teamInfo || !teamSelection || !departmentSelect) {
      console.error('Required DOM elements not found');
      return;
    }

    if (data.teamAssigned) {
      // User is assigned to a team, display the department
      teamInfo.innerHTML = `<p>You are already part of the ${data.department} team.</p>`;
    } else {
      // User is not assigned to a team, show the dropdown for department selection
      teamSelection.style.display = 'block';

      // Fetch the list of available teams from the database
      fetch('./get-teams.php')
        .then(response => response.json())
        .then(teams => {
          // Populate the department select dropdown with available teams
          teams.forEach(team => {
            const option = document.createElement('option');
            option.value = team.department;
            option.textContent = team.department;
            departmentSelect.appendChild(option);
          });
        })
        .catch(error => console.log('Error fetching teams:', error));
    }
  })
  .catch(error => console.log('Error fetching team status:', error));

  // Handle the button click to assign the team
  const assignTeamBtn = document.getElementById('assign-team-btn');
  if (assignTeamBtn) {
    assignTeamBtn.addEventListener('click', () => {
      const departmentSelect = document.getElementById('department-select');
      if (!departmentSelect) return;

      const selectedDepartment = departmentSelect.value;

      // Send the selected department to the backend to assign the user to the team
      fetch('./assign-team.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ department: selectedDepartment })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('You have successfully joined the ' + selectedDepartment + ' team!');
          // Update the UI to reflect the new team assignment
          const teamInfo = document.getElementById('team-info');
          const teamSelection = document.getElementById('team-selection');
          if (teamInfo) teamInfo.innerHTML = `<p>You are already part of the ${selectedDepartment} team.</p>`;
          if (teamSelection) teamSelection.style.display = 'none'; // Hide the selection dropdown
        } else {
          alert('Error joining the team.');
        }
      })
      .catch(error => console.log('Error joining the team:', error));
    });
  }

  // Show the account settings modal
  const accountSettings = document.getElementById('account-settings');
  if (accountSettings) {
    accountSettings.addEventListener('click', function() {
      const modal = document.getElementById('account-settings-modal');
      if (modal) modal.style.display = 'flex';
    });
  }

  // Close the modal
  const closeAccountSettings = document.getElementById('close-account-settings');
  if (closeAccountSettings) {
    closeAccountSettings.addEventListener('click', function() {
      const modal = document.getElementById('account-settings-modal');
      if (modal) modal.style.display = 'none';
    });
  }

  // Enable/disable username input based on checkbox
  const changeUsername = document.getElementById('change-username');
  if (changeUsername) {
    changeUsername.addEventListener('change', function() {
      const usernameInput = document.getElementById('new-username');
      if (!usernameInput) return;
      
      usernameInput.disabled = !this.checked;
      usernameInput.required = this.checked;
    });
  }

  // Enable/disable password input based on checkbox
  const changePassword = document.getElementById('change-password');
  if (changePassword) {
    changePassword.addEventListener('change', function() {
      const passwordInput = document.getElementById('new-password');
      if (!passwordInput) return;
      
      passwordInput.disabled = !this.checked;
      passwordInput.required = this.checked;
    });
  }

  // Handle the form submission to update the username and password
  const accountSettingsForm = document.getElementById('account-settings-form');
  if (accountSettingsForm) {
    accountSettingsForm.addEventListener('submit', function(event) {
      event.preventDefault();

      const newUsername = document.getElementById('new-username')?.value;
      const newPassword = document.getElementById('new-password')?.value;
      const changeUsername = document.getElementById('change-username');

      // Only check username if it's being changed
      let usernameValid = true;
      if (changeUsername?.checked) {
        if (!newUsername) {
          alert('Please provide a new username!');
          return;
        }
        
        // Check if the username already exists
        fetch('../../php/check-username.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ username: newUsername })
        })
        .then(response => response.json())
        .then(data => {
          const usernameError = document.getElementById('username-error');
          if (!data.available) {
            if (usernameError) usernameError.style.display = 'inline';
            usernameValid = false;
          } else {
            if (usernameError) usernameError.style.display = 'none';
            updateAccount(newUsername, newPassword);
          }
        })
        .catch(error => console.log('Error:', error));
      } else {
        updateAccount(newUsername, newPassword); // No need to check username if not changing it
      }
    });
  }
});

// Function to handle account update
function updateAccount(newUsername, newPassword) {
  const changeUsername = document.getElementById('change-username');
  const changePassword = document.getElementById('change-password');
  
  const updatedUsername = changeUsername?.checked ? newUsername : null;
  const updatedPassword = changePassword?.checked ? newPassword : null;

  fetch('../../php/update-account.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      username: updatedUsername,
      password: updatedPassword
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Account updated successfully!');
      // Close the modal
      const modal = document.getElementById('account-settings-modal');
      if (modal) modal.style.display = 'none';
      // Optionally update the username in the navbar without reloading
      if (updatedUsername) {
        const usernameSpan = document.querySelector('#navbar .nav-left span');
        if (usernameSpan) usernameSpan.textContent = `Hello, ${updatedUsername}`;
      }
    } else {
      alert('Error updating account!');
    }
  })
  .catch(error => console.log('Error:', error));
}