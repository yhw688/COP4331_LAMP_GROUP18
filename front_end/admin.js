const urlBase = 'https://yihanwang.fit/api';
const extension = 'php';

let userId = 0;
let firstName = "";
let lastName = "";

function readCookie()
{
        userId = -1;
        let data = document.cookie;
        let splits = data.split(",");
        for(var i = 0; i < splits.length; i++)
        {
                let thisOne = splits[i].trim();
                let tokens = thisOne.split("=");
                if( tokens[0] == "firstName" )
                {
                        firstName = tokens[1];
                }
                else if( tokens[0] == "lastName" )
                {
                        lastName = tokens[1];
                }
                else if( tokens[0] == "userId" )
                {
                        userId = parseInt( tokens[1].trim() );
                }
        }

        if( userId < 0 )
        {
                window.location.href = "index.html";
        }
        else
        {
                document.getElementById("userName").innerHTML = "Welcome, " + firstName + " " + lastName;
        }
}

function doLogout()
{
        userId = 0;
        firstName = "";
        lastName = "";
        document.cookie = "firstName= ; expires = Thu, 01 Jan 1970 00:00:00 GMT; path=/";
        window.location.href = "index.html";
}

function searchUsers() {
    let searchInput = document.getElementById("userSearchText");
    let srch = searchInput ? searchInput.value.trim() : "";
    
    let url = urlBase + '/admin/users.php?q=' + srch;
    let xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.withCredentials = true; 
    
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            let jsonObject = JSON.parse(xhr.responseText);
            let users = jsonObject.users || [];
            let userList = document.getElementById("userList"); // Assumes this ID is in your admin.html table
            
            if (users.length < 1) {
                userList.innerHTML = '<tr><td colspan="5" class="emptyState">No users found.</td></tr>';
                return;
            }

            let rows = "";
            for (let i = 0; i < users.length; i++) {
                let user = users[i];
                let statusText = user.IsEnabled ? "Active" : "Disabled";
                let toggleButtonText = user.IsEnabled ? "Disable" : "Enable";
                
                rows += "<tr>";
                rows += "<td>" + user.FirstName + " " + user.LastName + "</td>";
                rows += "<td>" + user.Login + "</td>";
                rows += "<td>" + user.Role + "</td>";
                rows += "<td>" + statusText + "</td>";
                rows += "<td>";
                rows += "<div class='actionButtons'>";
                rows += "<button type='button' class='editButton' onclick='changeUserPassword(" + user.ID + ")'>Change Pass</button>";
                // Prevents the admin from disabling themselves
                if (user.ID !== userId) {
                    rows += "<button type='button' class='deleteButton' onclick='toggleUserStatus(" + user.ID + ", " + user.IsEnabled + ")'>" + toggleButtonText + "</button>";
                }
                rows += "</div>";
                rows += "</td>";
                rows += "</tr>";
            }
            userList.innerHTML = rows;
        }
    };
    xhr.send();
}

function searchAdminContacts() {
    let searchInput = document.getElementById("contactSearchText");
    let srch = searchInput ? searchInput.value.trim() : "";
    
    let url = urlBase + '/admin/contacts.php?q=' + srch;
    let xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.withCredentials = true;
    
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            let jsonObject = JSON.parse(xhr.responseText);
            let contacts = jsonObject.contacts || [];
            let contactList = document.getElementById("adminContactList"); // Assumes this ID in your admin.html
            
            if (contacts.length < 1) {
                contactList.innerHTML = '<tr><td colspan="5" class="emptyState">No contacts found.</td></tr>';
                return;
            }

            let rows = "";
            for (let i = 0; i < contacts.length; i++) {
                let contact = contacts[i];
                rows += "<tr>";
                rows += "<td>" + contact.FirstName + " " + contact.LastName + "</td>";
                rows += "<td>" + contact.Phone + "</td>";
                rows += "<td>" + contact.Email + "</td>";
                rows += "<td>" + contact.OwnerLogin + "</td>"; // Shows which user owns this contact
                rows += "</tr>";
            }
            contactList.innerHTML = rows;
        }
    };
    xhr.send();
}

function toggleUserStatus(userIdToToggle, currentStatus) {
    let action = currentStatus === 1 ? "disable" : "enable";
    let url = urlBase + '/admin/users.php?id=' + userIdToToggle;
    
    let xhr = new XMLHttpRequest();
    xhr.open("PUT", url, true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");
    
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            searchUsers(); // Refresh the table after updating
        }
    };
    
    let tmp = { action: action };
    xhr.send(JSON.stringify(tmp));
}

function changeUserPassword(targetUserId) {
    let newPassword = prompt("Enter the new password for this user:");
    if (!newPassword || newPassword.trim() === "") return;

    let url = urlBase + '/admin/users.php?id=' + targetUserId;
    
    let xhr = new XMLHttpRequest();
    xhr.open("PUT", url, true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");
    
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            alert("Password updated successfully.");
        } else if (this.readyState == 4) {
            let err = JSON.parse(xhr.responseText);
            alert("Error: " + err.error);
        }
    };
    
    let tmp = { 
        action: "changePassword", 
        newPassword: newPassword 
    };
    xhr.send(JSON.stringify(tmp));
}