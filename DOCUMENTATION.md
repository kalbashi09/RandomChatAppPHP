# 📄 PROJECT DOCUMENTATION: RandomChatAppPHP

**Project Title:** RandomChatAppPHP  
**Developer:** John David D. Paul
**Date:** May 18, 2026  
**Version:** 1.0 (Initial Release)

---

## 1. Introduction

**RandomChatAppPHP** is a real-time, web-based communication platform designed to connect strangers through random group matching. Unlike traditional chat applications that require account creation or friend requests, this application focuses on spontaneity and anonymity. By utilizing WebSocket technology, the application provides a seamless, low-latency experience where users can instantly enter a "waiting room" and be paired with other users based on their preferred group size.

---

## 2. Objectives of the Website

The primary goals of this project are:

- **Facilitate Spontaneous Interaction**: To create a space where users can meet and interact with random people globally/locally without the friction of registration.
- **Implement Real-Time Bi-Directional Communication**: To utilize WebSockets (via Ratchet) to ensure messages are delivered instantly without the need for page refreshes.
- **Dynamic Group Scaling**: To allow users to define the social environment they prefer, whether it be a private 1v1 conversation or a small group discussion (up to 6 people).
- **Technical Exploration**: To demonstrate the use of event-driven programming in PHP using the ReactPHP ecosystem.

---

## 3. Tools and Technologies Used

### Backend

- **PHP 8.0+**: The primary server-side language.
- **Ratchet**: A PHP library for implementing WebSockets, allowing the server to push data to clients in real-time.
- **ReactPHP**: The underlying event-driven library that enables the server to handle multiple concurrent connections without blocking.
- **Composer**: Used for dependency management and autoloading.

### Frontend

- **HTML5**: For structured content and UI layout.
- **CSS3**: For responsive styling and a modern user interface.
- **Vanilla JavaScript (ES6+)**: For managing the WebSocket client-side logic and DOM manipulation.

### Infrastructure

- **Cloudflare Tunnel**: (Optional/Used in development) To expose the local WebSocket server to the public internet.

---

## 4. Website Features and Functions

### A. User Onboarding (Setup Screen)

- **Identity Assignment**: Users provide a nickname to be identified within the chat.
- **Preference Selection**: Users choose a group size (between 2 and 6 members) to determine who they are matched with.
- **Connection Status**: Real-time indicator showing if the client is successfully connected to the server.

### B. Random Matching Engine

- **Queue System**: The server maintains separate queues for different group sizes.
- **Automatic Pairing**: As soon as the required number of users for a specific group size is reached, the server automatically creates a unique group and notifies all members.
- **Matching Notifications**: Users are informed when they have been matched and provided with a list of their current group members.

### C. Real-Time Group Chat

- **Instant Messaging**: Messages are broadcasted only to the members of the specific matched group.
- **System Alerts**: The app provides automated notifications when a user joins or leaves the chat.
- **Session Management**: Users can leave the chat at any time, which triggers a cleanup process on the server to remove them from the group and the queue.

---

## 5. Database Structure

### Current State: In-Memory Management

Currently, the application does **not** utilize a persistent database. All data is stored in-memory on the server to ensure maximum speed and total anonymity.

- **`SplObjectStorage`**: Used to track all active WebSocket connections.
- **Waiting Queues**: PHP arrays that store `UserRequest` objects, indexed by group size.
- **Group Maps**: Associative arrays mapping `resourceId` to `groupId` and `groupId` to member lists.

### Future Implementation: Freedom Wall Posting Board

A persistent database (such as MySQL or PostgreSQL) will be integrated in the next phase to support the **Freedom Wall** feature.

**Proposed Database Schema for Freedom Wall:**

- **Table: `posts`**
  - `id` (INT, Primary Key, Auto-Increment)
  - `username` (VARCHAR) - The nickname of the poster.
  - `content` (TEXT) - The message posted to the wall.
  - `timestamp` (DATETIME) - When the post was created.
  - `is_anonymous` (BOOLEAN) - Whether to hide the username.

---

## 6. Screenshots of the Website

_(Note: Please insert actual screenshots in the spaces below)_

### 6.1 Setup Screen

`[Insert screenshot showing the Name input, Group Size dropdown, and the "Start" button]`

### 6.2 Waiting/Searching State

`[Insert screenshot showing the "Searching for a group..." status]`

### 6.3 Active Group Chat

`[Insert screenshot showing the chat window with messages and the member list]`

---

## 7. Conclusion

**RandomChatAppPHP** successfully implements the core logic of a random matching system using an event-driven PHP architecture. By bypassing traditional database requirements for the chat phase, the application achieves high performance and maintains user privacy. The current foundation is robust enough to support further expansions, most notably the integration of a persistent database for the Freedom Wall, which will transition the app from a purely transient chat tool to a community-driven social board.
