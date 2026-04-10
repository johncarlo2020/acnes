require('dotenv').config();
const express = require('express');
const Pusher  = require('pusher');
const cors    = require('cors');
const path    = require('path');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname)));

const pusher = new Pusher({
    appId:   process.env.PUSHER_APP_ID,
    key:     process.env.PUSHER_KEY,
    secret:  process.env.PUSHER_SECRET,
    cluster: process.env.PUSHER_CLUSTER,
    useTLS:  true,
});

const ALLOWED_ACTIONS = new Set(['up', 'down', 'left', 'right', 'bigger', 'smaller', 'reset']);

// Expose Pusher public config to client pages (key + cluster only — no secret)
app.get('/config', (req, res) => {
    res.json({
        key:     process.env.PUSHER_KEY,
        cluster: process.env.PUSHER_CLUSTER,
    });
});

// Remote control endpoint — validates action then triggers Pusher event
app.post('/control', async (req, res) => {
    const { action } = req.body ?? {};
    if (typeof action !== 'string' || !ALLOWED_ACTIONS.has(action)) {
        return res.status(400).json({ error: 'Invalid action' });
    }
    try {
        await pusher.trigger('game-control', 'canvas-move', { action });
        res.json({ ok: true });
    } catch (err) {
        console.error('Pusher trigger failed:', err.message);
        res.status(500).json({ error: 'Pusher trigger failed' });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Game:    http://localhost:${PORT}/bubblerohto.html`);
    console.log(`Remote:  http://localhost:${PORT}/remote.html`);
});
