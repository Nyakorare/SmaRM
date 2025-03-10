// Function to delete a user.
function deleteUser(username) {
    if (confirm(`Are you sure you want to delete the user: ${username}?`)) {
      window.location.href = `../../php/delete-user.php?username=${username}`;
    }
  }

  document.addEventListener("DOMContentLoaded", function() {
    const accountSettingsBtn = document.getElementById("account-settings");
    const modal = document.getElementById("account-settings-modal");
    const closeBtn = document.getElementById("close-account-settings");
    const accountSettingsForm = document.getElementById("account-settings-form");
    
    // Show modal on button click
    accountSettingsBtn.addEventListener("click", function() {
      modal.style.display = "block";
    });
  
    // Close modal
    closeBtn.addEventListener("click", function() {
      modal.style.display = "none";
    });
  
    // Handle form submission
    accountSettingsForm.addEventListener("submit", function(event) {
      event.preventDefault();
  
      const newPassword = document.getElementById("new-password").value;
  
      // Basic validation for password field
      if (newPassword.trim() === "") {
        alert("Please enter a new password.");
        return;
      }
  
      // Send the password update request via POST (AJAX can be used here)
      const formData = new FormData();
      formData.append("new-password", newPassword);
  
      fetch("accounts.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        alert(data);
        modal.style.display = "none"; // Hide modal after submission
      })
      .catch(error => console.error("Error updating password:", error));
    });
  });  