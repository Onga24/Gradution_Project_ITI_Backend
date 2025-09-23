


// const express = require('express');
// const http = require('http');
// const { Server } = require('socket.io');
// const cors = require('cors');

// const app = express();
// app.use(cors());
// const server = http.createServer(app);

// const io = new Server(server, {
//     cors: {
//         origin: '*',
//         methods: ['GET', 'POST']
//     }
// });

// io.on('connection', (socket) => {
//     console.log('a user connected:', socket.id);

//     socket.on('joinProject', (projectId) => {
//         socket.join(`project_${projectId}`);
//         console.log(`Socket ${socket.id} joined project_${projectId}`);
//     });

//     socket.on('joinUser', (userId) => {
//         socket.join(`user_${userId}`);
//         console.log(`User ${userId} joined personal room`);
//     });

//     socket.on('sendFriendRequest', ({ fromUserId, toUserId }) => {
//         io.to(`user_${toUserId}`).emit('receiveFriendRequest', { fromUserId });
//         console.log(`Friend request sent from ${fromUserId} to ${toUserId}`);
//     });

//     socket.on('acceptFriendRequest', ({ fromUserId, toUserId }) => {
//         io.to(`user_${fromUserId}`).emit('friendRequestAccepted', { by: toUserId });
//         console.log(`${toUserId} accepted friend request from ${fromUserId}`);
//     });

//     const room = `chat_${Math.min(userA, userB)}_${Math.max(userA, userB)}`;
//     socket.join(room);
//     io.to(`user_${userB}`).emit('conversationStarted', { room, with: userA });
//     socket.on('startConversation', ({ userA, userB }) => {
//         console.log(`Conversation started between ${userA} and ${userB} in room ${room}`);
//     });

//     socket.on('sendDirectMessage', ({ room, from, to, message }) => {
//         io.to(room).emit('receiveDirectMessage', { from, message });
//         console.log(`Message from ${from} to ${to} in ${room}: ${message}`);
//     });

//     // -----------------------------
//     // socket.on('codeChange', ({ projectId, code }) => {
//     //     socket.to(`project_${projectId}`).emit('receiveCode', code);
//     // });

// socket.on('codeChange', ({ projectId, fileId, code }) => {
//     socket.to(`project_${projectId}`).emit('receiveCode', { fileId, newContent: code });
//     console.log(`Code change in project ${projectId}, file ${fileId}`);
// });
//     socket.on('codeOutput', ({ projectId, output }) => {
//     socket.to(`project_${projectId}`).emit('receiveOutput', output);
//             console.log(`Execution result for project_${projectId}:`, output);
// });

//     socket.on('newMessage', (message) => {
//         const room = `project_${message.project_id}`;
//         io.to(room).emit('receiveMessage', message);
//         console.log(`New message in ${room}:`, message.body);
//     });

//     socket.on('typing', ({ projectId, user }) => {
//         socket.to(`project_${projectId}`).emit('userTyping', user);
//     });

//     socket.on('stopTyping', ({ projectId, user }) => {
//         socket.to(`project_${projectId}`).emit('userStopTyping', user);
//     });



// socket.on('sendDirectMessage', ({ room, from, to, message }) => {
//     io.to(room).emit('receiveDirectMessage', { from, message });
// });



// //     socket.on('cursorChange', ({ projectId, user, position }) => {
// //     socket.to(`project_${projectId}`).emit('userCursor', { user, position });
// // });


//     // 🔹 DISCONNECT
//     socket.on('disconnect', () => {
//         console.log('user disconnected:', socket.id);
//     });
// });

// const PORT = 3001;
// server.listen(PORT, () => console.log(`Socket.IO server running on port ${PORT}`));


const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

io.on('connection', (socket) => {
    console.log('a user connected:', socket.id);

    // Project collaboration events
    socket.on('joinProject', (projectId) => {
        socket.join(`project_${projectId}`);
        console.log(`Socket ${socket.id} joined project_${projectId}`);
    });

    // Code change events
    socket.on('codeChange', ({ projectId, fileId, code }) => {
        socket.to(`project_${projectId}`).emit('receiveCode', { fileId, newContent: code });
        console.log(`Code change in project ${projectId}, file ${fileId}`);
    });

    socket.on('codeOutput', ({ projectId, output }) => {
        socket.to(`project_${projectId}`).emit('receiveOutput', output);
        console.log(`Execution result for project_${projectId}:`, output);
    });

    // Chat events
    socket.on('newMessage', (message) => {
        const room = `project_${message.project_id}`;
        io.to(room).emit('receiveMessage', message);
        console.log(`New message in ${room}:`, message.body);
    });

    socket.on('typing', ({ projectId, user }) => {
        socket.to(`project_${projectId}`).emit('userTyping', user);
    });

    socket.on('stopTyping', ({ projectId, user }) => {
        socket.to(`project_${projectId}`).emit('userStopTyping', user);
    });

    // User room events
    socket.on('joinUser', (userId) => {
        socket.join(`user_${userId}`);
        console.log(`User ${userId} joined personal room`);
    });

    // Friend request events
    socket.on('sendFriendRequest', ({ fromUserId, toUserId }) => {
        io.to(`user_${toUserId}`).emit('receiveFriendRequest', { fromUserId });
        console.log(`Friend request sent from ${fromUserId} to ${toUserId}`);
    });

    socket.on('acceptFriendRequest', ({ fromUserId, toUserId }) => {
        io.to(`user_${fromUserId}`).emit('friendRequestAccepted', { by: toUserId });
        console.log(`${toUserId} accepted friend request from ${fromUserId}`);
    });

    // Direct messaging events
    socket.on('startConversation', ({ userA, userB }) => {
        const room = `chat_${Math.min(userA, userB)}_${Math.max(userA, userB)}`;
        socket.join(room);
        io.to(`user_${userB}`).emit('conversationStarted', { room, with: userA });
        console.log(`Conversation started between ${userA} and ${userB} in room ${room}`);
    });

    socket.on('sendDirectMessage', ({ room, from, to, message }) => {
        io.to(room).emit('receiveDirectMessage', { from, message });
        console.log(`Message from ${from} to ${to} in ${room}: ${message}`);
    });

    // Disconnect event
    socket.on('disconnect', () => {
        console.log('user disconnected:', socket.id);
    });

    // Error handling
    socket.on('error', (error) => {
        console.error('Socket error:', error);
    });
});

const PORT = 3001;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
});
