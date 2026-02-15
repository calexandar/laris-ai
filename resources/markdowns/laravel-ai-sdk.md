# Laravel AI SDK Technical Documentation

## Installation

The Laravel AI SDK can be installed via Composer:

```bash
composer require laravel/ai
```

## Configuration

After installation, publish the configuration:

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

## Supported Providers

The SDK supports multiple AI providers:

- **Text Generation**: OpenAI, Anthropic, Gemini, Groq, xAI
- **Image Generation**: OpenAI, Gemini, xAI
- **Text-to-Speech**: OpenAI, ElevenLabs
- **Speech-to-Text**: OpenAI, ElevenLabs, Mistral
- **Embeddings**: OpenAI, Gemini, Cohere, Jina

## Creating Agents

Generate an agent using Artisan:

```bash
php artisan make:agent VoiceAssistant
```

## Voice Features

### Text-to-Speech

```php
use Laravel\Ai\Audio;

$audio = Audio::of('Hello Laravel AI')
    ->female()
    ->generate();
```

### Speech-to-Text

```php
use Laravel\Ai\Transcription;

$transcript = Transcription::fromStorage('audio.mp3')
    ->generate();
```

## Vector Search

Enable semantic search with embeddings:

```php
Document::query()
    ->whereVectorSimilarTo('embedding', $query)
    ->get();
```
