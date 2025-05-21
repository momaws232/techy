/**
 * Real-time post updates and like functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Only run this code on topic pages where posts are displayed
    if (!document.querySelector('.post')) {
        return;
    }
    
    // Get the topic ID from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const topicId = urlParams.get('id');
    
    if (!topicId) {
        return;
    }
    
    // Store timestamps for real-time updates
    let lastUpdate = new Date().toISOString();
    
    // Add click handlers for like buttons
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.closest('.post-likes').dataset.postId;
            const isLiked = this.classList.contains('btn-primary');            // Send like/unlike request
            // Get the base URL by looking at the window location
            const baseUrl = window.location.pathname.includes('/topic.php') ? '' : '../';
            
            fetch(baseUrl + 'api/like-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    post_id: postId,
                    action: isLiked ? 'unlike' : 'like'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update like count
                    const likeCountElement = this.querySelector('.like-count');
                    likeCountElement.textContent = data.likes;
                    
                    // Update button style based on whether user has liked the post
                    if (data.user_likes) {
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                    } else {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline-primary');
                    }
                    
                    // Update text
                    const likeText = parseInt(data.likes) === 1 ? 'Like' : 'Likes';
                    this.innerHTML = `<i class="fas fa-thumbs-up"></i> <span class="like-count">${data.likes}</span> ${likeText}`;
                } else {
                    console.error('Error:', data.message);
                    // Optionally show error to user
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });    // Check for real-time updates periodically
    function checkForUpdates() {        // Get the base URL by looking at the window location
        const baseUrl = window.location.pathname.includes('/topic.php') ? '' : '../';
        
        // Add a console log for debugging
        console.log('Checking for updates since:', lastUpdate);
        
        fetch(baseUrl + 'api/get-post-updates.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                topic_id: topicId,
                last_update: lastUpdate
            })
        })
        .then(response => response.json())        .then(data => {
            console.log('Response from server:', data);
            
            if (data.success) {
                // Update timestamp for next check
                lastUpdate = data.timestamp;
                console.log('Updated timestamp to:', lastUpdate);
                
                // If there are new posts, show notification
                if (data.has_new_posts) {
                    console.log('New posts detected! Showing notification.');
                    showNewPostsNotification();
                }
                
                // Update like counts for all posts
                data.post_data.forEach(post => {
                    const postLikeElement = document.querySelector(`.post-likes[data-post-id="${post.id}"] .like-count`);
                    if (postLikeElement) {
                        postLikeElement.textContent = post.like_count;
                        
                        // Update the button style if needed
                        const likeButton = postLikeElement.closest('.like-button');
                        if (likeButton) {
                            const likeText = parseInt(post.like_count) === 1 ? 'Like' : 'Likes';
                            if (post.user_likes == "1") {
                                likeButton.classList.remove('btn-outline-primary');
                                likeButton.classList.add('btn-primary');
                            } else {
                                likeButton.classList.remove('btn-primary');
                                likeButton.classList.add('btn-outline-primary');
                            }
                            likeButton.innerHTML = `<i class="fas fa-thumbs-up"></i> <span class="like-count">${post.like_count}</span> ${likeText}`;
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
        });
    }
      // Display notification for new posts
    function showNewPostsNotification() {
        // Check if notification already exists
        if (document.getElementById('new-posts-notification')) {
            return;
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.id = 'new-posts-notification';
        notification.className = 'alert alert-info text-center sticky-top';
        notification.innerHTML = 'New posts have been added to this topic. <a href="javascript:void(0)" onclick="window.location.reload();" class="alert-link">Refresh</a> to see them.';
        
        // Insert in the notifications container
        const notificationContainer = document.getElementById('notifications-container');
        if (notificationContainer) {
            notificationContainer.appendChild(notification);
        } else {
            // Fallback to inserting at the top of the post list
            const postContainer = document.querySelector('.card-body');
            if (postContainer) {
                postContainer.insertBefore(notification, postContainer.firstChild);
            }
        }
    }
      // Check for updates every 15 seconds
    setInterval(checkForUpdates, 15000);
    
    // Do an initial check on page load after a slight delay
    setTimeout(checkForUpdates, 2000);
});
