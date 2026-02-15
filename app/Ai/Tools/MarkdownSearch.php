<?php

namespace App\Ai\Tools;

use App\Models\Document;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class MarkdownSearch implements Tool
{
    /**
     * Get description of tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search through the markdown knowledge base to find relevant information. Use this when the user asks questions that might be answered by the stored markdown files.';
    }

    /**
     * Execute tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $request->string('query');

        // Search for similar documents
        $documents = Document::searchSimilar($query, 3);

        if ($documents->isEmpty()) {
            return "No relevant information found in the knowledge base for the query: '{$query}'";
        }

        $results = [];
        foreach ($documents as $document) {
            $results[] = "Title: {$document->title}\nContent: {$document->content}";
        }

        return "Found relevant information:\n\n".implode("\n\n---\n\n", $results);
    }

    /**
     * Get tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required()->description('The search query to find relevant information in the markdown knowledge base'),
        ];
    }
}
