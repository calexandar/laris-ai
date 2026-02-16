import { Head } from '@inertiajs/react';
import { useState, useRef } from 'react';

interface WelcomeProps {
    csrfToken: string;
}

export default function Welcome({ csrfToken }: WelcomeProps) {
    const [isRecording, setIsRecording] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    const [isPlaying, setIsPlaying] = useState(false);
    const [transcription, setTranscription] = useState('');
    const [response, setResponse] = useState('');
    const [audioUrl, setAudioUrl] = useState('');
    const [error, setError] = useState('');

    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const audioChunksRef = useRef<Blob[]>([]);

    const startRecording = async () => {
        try {
            setError('');
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mediaRecorder = new MediaRecorder(stream);

            mediaRecorderRef.current = mediaRecorder;
            audioChunksRef.current = [];

            mediaRecorder.ondataavailable = (event) => {
                audioChunksRef.current.push(event.data);
            };

            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunksRef.current, { type: audioChunksRef.current[0]?.type || 'audio/webm' });
                await processAudio(audioBlob);

                // Stop all tracks
                stream.getTracks().forEach((track: MediaStreamTrack) => track.stop());
            };

            mediaRecorder.start();
            setIsRecording(true);
        } catch (err) {
            setError('Microphone access denied. Please allow microphone access to use voice features.');
            console.error('Error accessing microphone:', err);
        }
    };

    const stopRecording = () => {
        if (mediaRecorderRef.current && isRecording) {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
            setIsProcessing(true);
        }
    };

    const processAudio = async (audioBlob: Blob) => {
        try {
            setError('');
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.wav');

            const headers: Record<string, string> = {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            };

            const response = await fetch('/api/voice/interact', {
                method: 'POST',
                body: formData,
                headers,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`HTTP error! status: ${response.status} - ${JSON.stringify(errorData)}`);
            }

            const data = await response.json();

            if (data.success) {
                setTranscription(data.transcription);
                setResponse(data.response);
                setAudioUrl(data.audio_url);
                playAudio(data.audio_url);
            } else {
                setError(data.error || 'Something went wrong. Please try again.');
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to process audio. Please try again.';
            setError(errorMessage);
            console.error('Error processing audio:', err);
        } finally {
            setIsProcessing(false);
        }
    };

    const playAudio = (url: string) => {
        const audio = new Audio(url);
        audio.onplay = () => setIsPlaying(true);
        audio.onended = () => setIsPlaying(false);
        audio.onerror = () => {
            setIsPlaying(false);
            setError('Failed to play audio response');
        };
        audio.play().catch((err) => {
            console.error('Error playing audio:', err);
            setError('Failed to play audio response');
        });
    };

    const getButtonClass = () => {
        if (isRecording) return 'bg-red-500 hover:bg-red-600 animate-pulse';
        if (isProcessing) return 'bg-yellow-500 hover:bg-yellow-600 animate-spin';
        if (isPlaying) return 'bg-green-500 hover:bg-green-600 animate-pulse';
        return 'bg-blue-500 hover:bg-blue-600';
    };

    const getButtonText = () => {
        if (isRecording) return 'Recording...';
        if (isProcessing) return 'Processing...';
        if (isPlaying) return 'Speaking...';
        return 'Start Talking';
    };

    return (
        <>
            <Head title="Voice Assistant" />
            <div className="flex min-h-screen items-center justify-center bg-gray-100 p-4">
                <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
                    <div className="text-center">
                        <h1 className="mb-2 text-3xl font-bold text-gray-800">Voice Assistant</h1>
                        <p className="mb-8 text-gray-600">Click the button and ask me anything about the knowledge base</p>

                        {/* Voice Button */}
                        <div className="mb-8">
                            <button
                                onClick={isRecording ? stopRecording : startRecording}
                                disabled={isProcessing || isPlaying}
                                className={`h-32 w-32 rounded-full text-lg font-semibold text-white transition-all duration-200 ${getButtonClass()} ${isProcessing || isPlaying ? 'cursor-not-allowed' : 'cursor-pointer'}`}
                            >
                                {getButtonText()}
                            </button>
                        </div>

                        {/* Error Message */}
                        {error && <div className="mb-6 rounded border border-red-400 bg-red-100 p-4 text-red-700">{error}</div>}

                        {/* Transcription */}
                        {transcription && (
                            <div className="mb-6 text-left">
                                <h3 className="mb-2 font-semibold text-gray-700">You asked:</h3>
                                <div className="rounded bg-gray-100 p-3 text-gray-800">{transcription}</div>
                            </div>
                        )}

                        {/* Response */}
                        {response && (
                            <div className="mb-6 text-left">
                                <h3 className="mb-2 font-semibold text-gray-700">Assistant responded:</h3>
                                <div className="rounded bg-blue-100 p-3 text-gray-800">{response}</div>
                            </div>
                        )}

                        {/* Audio Controls */}
                        {audioUrl && !isPlaying && (
                            <div className="mt-4">
                                <button
                                    onClick={() => playAudio(audioUrl)}
                                    className="rounded bg-green-500 px-4 py-2 text-white transition-colors duration-200 hover:bg-green-600"
                                >
                                    🔊 Play Response
                                </button>
                            </div>
                        )}

                        {/* Instructions */}
                        <div className="mt-8 text-sm text-gray-500">
                            <p>• Click "Start Talking" to begin recording</p>
                            <p>• Click again to stop recording and get a response</p>
                            <p>• Allow microphone access when prompted</p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
