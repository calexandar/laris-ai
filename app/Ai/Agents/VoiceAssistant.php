<?php

namespace App\Ai\Agents;

use App\Ai\Tools\MarkdownSearch;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class VoiceAssistant implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTION'
You are a voice assistant that helps users by answering questions based on a knowledge base of markdown files.

Your role:
1. Listen to user questions and provide helpful answers
2. Use the MarkdownSearch tool to find relevant information from the knowledge base
3. If you find relevant information, use it to answer the user's question
4. If no relevant information is found, provide a helpful response based on general knowledge
5. Always be concise and conversational since your responses will be spoken aloud

Guidelines:
- Use the search tool first before providing answers from general knowledge
- Keep answers brief and easy to understand when spoken
- If multiple documents are found, synthesize the information
- Always cite your sources when using information from the knowledge base
- Be friendly and helpful in your responses
INSTRUCTION;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new MarkdownSearch,
        ];
    }
}
