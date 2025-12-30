<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Piano</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }

        .piano-container {
            background: #333;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
        }

        .piano {
            display: flex;
            position: relative;
            height: 200px;
        }

        .key {
            cursor: pointer;
            user-select: none;
            transition: all 0.1s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            font-weight: bold;
            border: 1px solid #ccc;
        }

        .key-freq {
            font-size: 9px;
            font-weight: normal;
            opacity: 0.6;
            margin-bottom: 2px;
        }

        .key-note {
            margin-bottom: 10px;
        }

        .white-key {
            width: 60px;
            height: 200px;
            background: linear-gradient(to bottom, #fff 0%, #f0f0f0 100%);
            color: #333;
            padding: 5px;
            z-index: 1;
        }

        .white-key:hover {
            background: linear-gradient(to bottom, #f0f0f0 0%, #e0e0e0 100%);
        }

        .white-key.active {
            background: linear-gradient(to bottom, #ddd 0%, #ccc 100%);
            transform: scale(0.98);
        }

        .black-key {
            width: 40px;
            height: 120px;
            background: linear-gradient(to bottom, #333 0%, #000 100%);
            color: white;
            position: absolute;
            z-index: 2;
            padding: 5px;
        }

        .black-key .key-freq {
            font-size: 8px;
        }

        .black-key:hover {
            background: linear-gradient(to bottom, #444 0%, #111 100%);
        }

        .black-key.active {
            background: linear-gradient(to bottom, #555 0%, #222 100%);
            transform: scale(0.95);
        }

        /* Position black keys */
        .black-key:nth-child(2) { left: 40px; }
        .black-key:nth-child(4) { left: 100px; }
        .black-key:nth-child(7) { left: 220px; }
        .black-key:nth-child(9) { left: 280px; }
        .black-key:nth-child(11) { left: 340px; }
        .black-key:nth-child(14) { left: 460px; }
        .black-key:nth-child(16) { left: 520px; }

        .controls {
            margin-top: 20px;
            text-align: center;
        }

        .volume-control {
            margin: 10px;
            color: white;
        }

        .volume-control input[type="range"] {
            margin: 0 10px;
            width: 200px;
        }

        .instructions {
            color: white;
            text-align: center;
            margin-top: 20px;
            opacity: 0.8;
        }

        .octave-control {
            margin: 10px;
            color: white;
        }

        .octave-control button {
            background: #555;
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
        }

        .octave-control button:hover {
            background: #666;
        }

        .tuning-control {
            margin: 10px;
            color: white;
        }

        .tuning-control select {
            background: #555;
            color: white;
            border: 1px solid #777;
            padding: 6px 10px;
            border-radius: 4px;
            margin: 0 5px;
            cursor: pointer;
        }

        .tuning-control select:hover {
            background: #666;
        }

        #rootKeySelect {
            display: none;
        }
    </style>
</head>
<body>
    <h1>🎹 Virtual Piano</h1>
    
    <div class="piano-container">
        <div class="piano" id="piano">
            <!-- White Keys -->
            <div class="key white-key" data-note="C" data-key="a" data-octave="4"><div class="key-freq"></div><div class="key-note">C</div></div>
            <div class="key black-key" data-note="C#" data-key="w" data-octave="4"><div class="key-freq"></div><div class="key-note">C#</div></div>
            <div class="key white-key" data-note="D" data-key="s" data-octave="4"><div class="key-freq"></div><div class="key-note">D</div></div>
            <div class="key black-key" data-note="D#" data-key="e" data-octave="4"><div class="key-freq"></div><div class="key-note">D#</div></div>
            <div class="key white-key" data-note="E" data-key="d" data-octave="4"><div class="key-freq"></div><div class="key-note">E</div></div>
            <div class="key white-key" data-note="F" data-key="f" data-octave="4"><div class="key-freq"></div><div class="key-note">F</div></div>
            <div class="key black-key" data-note="F#" data-key="t" data-octave="4"><div class="key-freq"></div><div class="key-note">F#</div></div>
            <div class="key white-key" data-note="G" data-key="g" data-octave="4"><div class="key-freq"></div><div class="key-note">G</div></div>
            <div class="key black-key" data-note="G#" data-key="y" data-octave="4"><div class="key-freq"></div><div class="key-note">G#</div></div>
            <div class="key white-key" data-note="A" data-key="h" data-octave="4"><div class="key-freq"></div><div class="key-note">A</div></div>
            <div class="key black-key" data-note="A#" data-key="u" data-octave="4"><div class="key-freq"></div><div class="key-note">A#</div></div>
            <div class="key white-key" data-note="B" data-key="j" data-octave="4"><div class="key-freq"></div><div class="key-note">B</div></div>
            <div class="key white-key" data-note="C" data-key="k" data-octave="5"><div class="key-freq"></div><div class="key-note">C</div></div>
            <div class="key black-key" data-note="C#" data-key="o" data-octave="5"><div class="key-freq"></div><div class="key-note">C#</div></div>
            <div class="key white-key" data-note="D" data-key="l" data-octave="5"><div class="key-freq"></div><div class="key-note">D</div></div>
            <div class="key black-key" data-note="D#" data-key="p" data-octave="5"><div class="key-freq"></div><div class="key-note">D#</div></div>
            <div class="key white-key" data-note="E" data-key=";" data-octave="5"><div class="key-freq"></div><div class="key-note">E</div></div>
        </div>
        
        <div class="controls">
            <div class="volume-control">
                <label for="volume">Volume:</label>
                <input type="range" id="volume" min="0" max="1" step="0.1" value="0.5">
                <span id="volume-display">50%</span>
            </div>
            
            <div class="octave-control">
                <label>Octave:</label>
                <button onclick="changeOctave(-1)">-</button>
                <span id="octave-display">4</span>
                <button onclick="changeOctave(1)">+</button>
            </div>
            
            <div class="tuning-control">
                <label for="tuningSystem">Tuning:</label>
                <select id="tuningSystem" onchange="changeTuningSystem()">
                    <option value="equal">Equal Temperament</option>
                    <option value="wellTempered">Well-Tempered (Werckmeister III)</option>
                </select>
                <span id="rootKeySelect">
                    <label for="rootKey">Root Key:</label>
                    <select id="rootKey" onchange="updateTuning()">
                        <option value="C">C</option>
                        <option value="C#">C#</option>
                        <option value="D">D</option>
                        <option value="D#">D#</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                        <option value="F#">F#</option>
                        <option value="G">G</option>
                        <option value="G#">G#</option>
                        <option value="A">A</option>
                        <option value="A#">A#</option>
                        <option value="B">B</option>
                    </select>
                </span>
            </div>
        </div>
    </div>
    
    <div class="instructions">
        <p>🎵 Click keys or use keyboard: A S D F G H J K L ; (white keys) | W E T Y U O P (black keys)</p>
        <p>Use octave buttons to change pitch range • Try Well-Tempered tuning for historical temperament</p>
    </div>

    <script>
        let audioContext;
        let masterVolume = 0.5;
        let currentOctave = 4;
        let activeNotes = new Map();
        let tuningSystem = 'equal';
        let rootKey = 'C';

        // Werckmeister III temperament (cents deviation from equal temperament)
        // These small adjustments create more pure intervals in common keys
        const werckmeisterIII = {
            'C': 0,
            'C#': -9.775,
            'D': -7.82,
            'D#': -5.865,
            'E': -9.775,
            'F': -1.955,
            'F#': -11.73,
            'G': -3.91,
            'G#': -7.82,
            'A': -11.73,
            'A#': -3.91,
            'B': -7.82
        };

        // Note frequencies (C4 = middle C)
        const noteFrequencies = {
            'C': 261.63,
            'C#': 277.18,
            'D': 293.66,
            'D#': 311.13,
            'E': 329.63,
            'F': 349.23,
            'F#': 369.99,
            'G': 392.00,
            'G#': 415.30,
            'A': 440.00,
            'A#': 466.16,
            'B': 493.88
        };

        // Initialize audio context
        function initAudio() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
        }

        // Update frequency displays for all keys
        function updateAllFrequencies() {
            const keys = document.querySelectorAll('.key');
            keys.forEach(key => {
                const noteName = key.dataset.note;
                const octave = parseInt(key.dataset.octave);
                const frequency = getFrequency(noteName, octave);
                const freqDisplay = key.querySelector('.key-freq');
                if (freqDisplay) {
                    freqDisplay.textContent = frequency.toFixed(1) + 'Hz';
                }
            });
        }

        // Calculate frequency based on tuning system
        function getFrequency(noteName, octave = currentOctave) {
            let baseFreq = noteFrequencies[noteName];
            
            if (tuningSystem === 'wellTempered') {
                // Calculate note position relative to root key
                const notes = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
                const noteIndex = notes.indexOf(noteName);
                const rootIndex = notes.indexOf(rootKey);
                
                // Rotate the temperament based on root key
                let relativeIndex = (noteIndex - rootIndex + 12) % 12;
                const noteNames = notes.slice(rootIndex).concat(notes.slice(0, rootIndex));
                const originalNote = noteNames[relativeIndex];
                
                // Apply Werckmeister III temperament (convert cents to frequency ratio)
                const centsDeviation = werckmeisterIII[originalNote];
                const ratio = Math.pow(2, centsDeviation / 1200);
                baseFreq = baseFreq * ratio;
            }
            
            return baseFreq * Math.pow(2, octave - 4);
        }

        // Create and play a note
        function playNote(noteName, octave = currentOctave) {
            initAudio();
            
            const frequency = getFrequency(noteName, octave);
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
            oscillator.type = 'triangle'; // Warmer sound than sine wave
            
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(masterVolume * 0.3, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1.5);
            
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 1.5);
            
            return { oscillator, gainNode };
        }

        // Handle key press (visual and audio)
        function pressKey(keyElement, noteName, octave = currentOctave) {
            keyElement.classList.add('active');
            
            // Stop any existing note for this key
            const keyId = keyElement.dataset.key;
            if (activeNotes.has(keyId)) {
                const { gainNode } = activeNotes.get(keyId);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);
                activeNotes.delete(keyId);
            }
            
            // Play new note
            const noteData = playNote(noteName, octave);
            activeNotes.set(keyId, noteData);
        }

        // Handle key release (visual)
        function releaseKey(keyElement) {
            keyElement.classList.remove('active');
        }

        // Set up event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const keys = document.querySelectorAll('.key');
            const volumeSlider = document.getElementById('volume');
            const volumeDisplay = document.getElementById('volume-display');
            
            // Mouse events for piano keys
            keys.forEach(key => {
                key.addEventListener('mousedown', function() {
                    // Determine octave for mouse clicks based on key position
                    let octave = currentOctave;
                    if (['k', 'o', 'l', 'p', ';'].includes(this.dataset.key)) {
                        octave = currentOctave + 1; // Higher octave for right side keys
                    }
                    pressKey(this, this.dataset.note, octave);
                });
                
                key.addEventListener('mouseup', function() {
                    releaseKey(this);
                });
                
                key.addEventListener('mouseleave', function() {
                    releaseKey(this);
                });
            });
            
            // Keyboard events
            document.addEventListener('keydown', function(e) {
                // Don't trigger piano keys when typing in form elements
                if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return;
                }
                
                if (e.repeat) return; // Ignore key repeat
                
                const keyMap = {
                    'a': 'C', 'w': 'C#', 's': 'D', 'e': 'D#', 'd': 'E',
                    'f': 'F', 't': 'F#', 'g': 'G', 'y': 'G#', 'h': 'A',
                    'u': 'A#', 'j': 'B', 'k': 'C', 'o': 'C#', 'l': 'D',
                    'p': 'D#', ';': 'E'
                };
                
                const note = keyMap[e.key.toLowerCase()];
                if (note) {
                    const keyElement = document.querySelector(`[data-key="${e.key.toLowerCase()}"]`);
                    if (keyElement && !keyElement.classList.contains('active')) {
                        let octaveOffset = 0;
                        if (['k', 'o', 'l', 'p', ';'].includes(e.key.toLowerCase())) {
                            octaveOffset = 1; // Higher octave for right side keys
                        }
                        pressKey(keyElement, note, currentOctave + octaveOffset);
                    }
                }
            });
            
            document.addEventListener('keyup', function(e) {
                // Don't trigger piano keys when typing in form elements
                if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return;
                }
                
                const keyMap = {
                    'a': 'C', 'w': 'C#', 's': 'D', 'e': 'D#', 'd': 'E',
                    'f': 'F', 't': 'F#', 'g': 'G', 'y': 'G#', 'h': 'A',
                    'u': 'A#', 'j': 'B', 'k': 'C', 'o': 'C#', 'l': 'D',
                    'p': 'D#', ';': 'E'
                };
                
                if (keyMap[e.key.toLowerCase()]) {
                    const keyElement = document.querySelector(`[data-key="${e.key.toLowerCase()}"]`);
                    if (keyElement) {
                        releaseKey(keyElement);
                    }
                }
            });
            
            // Volume control
            volumeSlider.addEventListener('input', function() {
                masterVolume = parseFloat(this.value);
                volumeDisplay.textContent = Math.round(masterVolume * 100) + '%';
            });
            
            // Initialize frequency displays
            updateAllFrequencies();
        });

        // Octave control functions
        function changeOctave(direction) {
            currentOctave = Math.max(1, Math.min(7, currentOctave + direction));
            document.getElementById('octave-display').textContent = currentOctave;
        }

        // Tuning system control functions
        function changeTuningSystem() {
            tuningSystem = document.getElementById('tuningSystem').value;
            const rootKeySelect = document.getElementById('rootKeySelect');
            
            if (tuningSystem === 'wellTempered') {
                rootKeySelect.style.display = 'inline';
            } else {
                rootKeySelect.style.display = 'none';
            }
            
            updateAllFrequencies();
        }

        function updateTuning() {
            rootKey = document.getElementById('rootKey').value;
            updateAllFrequencies();
        }

        // Initialize audio context on first user interaction
        document.addEventListener('click', initAudio, { once: true });
        document.addEventListener('keydown', initAudio, { once: true });
    </script>
</body>
</html>
