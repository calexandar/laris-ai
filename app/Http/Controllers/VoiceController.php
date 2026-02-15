<?php

namespace App\Http\Controllers;

use App\Ai\Agents\VoiceAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Transcription;

class VoiceController extends Controller
{
    /**
     * Transcribe voice recording to text.
     */
    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,m4a,ogg|max:10240', // 10MB max
        ]);

        try {
            // Store the uploaded audio temporarily
            $path = $request->file('audio')->store('temp/audio');

            // Transcribe using OpenAI
            $transcription = Transcription::fromStorage($path)->generate();

            // Clean up temporary file
            Storage::delete($path);

            return response()->json([
                'text' => $transcription->text,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Voice transcription failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Voice transcription failed. Please check your microphone and try again.',
                'success' => false,
            ], 500);
        }
    }

    /**
     * Generate speech from text using ElevenLabs.
     */
    public function speak(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'voice' => 'sometimes|string|in:male,female,neutral',
        ]);

        try {
            $voice = $request->input('voice', 'female');

            // Generate audio using ElevenLabs
            $audio = Audio::of($request->input('text'))
                ->{$voice}()
                ->generate();

            // Store the audio file
            $path = $audio->store('audio/responses', 'public');

            return response()->json([
                'audio_url' => Storage::url($path),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Speech generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Speech generation failed. Please try again later.',
                'success' => false,
            ], 500);
        }
    }

    /**
     * Process user question and generate AI response.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        try {
            $assistant = new VoiceAssistant;
            $response = $assistant->prompt($request->input('question'));

            return response()->json([
                'response' => (string) $response,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('AI processing failed', [
                'question' => $request->input('question'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'AI processing failed. Please try rephrasing your question.',
                'success' => false,
            ], 500);
        }
    }

    /**
     * Complete voice interaction pipeline.
     */
    public function interact(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,m4a,ogg|max:10240',
            'voice' => 'sometimes|string|in:male,female,neutral',
        ]);

        try {
            // Step 1: Transcribe audio to text
            $transcription = Transcription::fromStorage(
                $request->file('audio')->store('temp/audio')
            )->generate();

            $question = $transcription->text;

            // Step 2: Get AI response
            $assistant = new VoiceAssistant;
            $aiResponse = $assistant->prompt($question);
            $responseText = (string) $aiResponse;

            // Step 3: Generate speech from response
            $voice = $request->input('voice', 'female');
            $audio = Audio::of($responseText)->{$voice}()->generate();
            $audioPath = $audio->store('audio/responses', 'public');

            return response()->json([
                'transcription' => $question,
                'response' => $responseText,
                'audio_url' => Storage::url($audioPath),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Voice interaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up temporary file if it exists
            if (isset($transcription) && method_exists($transcription, 'text')) {
                Storage::delete($request->file('audio')->store('temp/audio'));
            }

            return response()->json([
                'error' => 'Voice interaction failed. Please try again.',
                'success' => false,
            ], 500);
        }
    }
}
