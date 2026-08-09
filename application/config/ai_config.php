<?php defined('BASEPATH') or exit('No direct script access allowed');

// Gemini Flash API key — get a free key at https://aistudio.google.com/app/apikey
// Free tier: 15 requests/minute, 1 million tokens/day, no credit card required
// Paste your key below between the single quotes:
$config['gemini_api_key'] = '';

// Model — gemini-1.5-flash is fast and cheap; change to gemini-1.5-pro for richer answers
$config['gemini_model'] = 'gemini-1.5-flash';

// Max tokens Gemini is allowed to generate per response (keep low for cost control)
$config['gemini_max_output_tokens'] = 400;

// Conversation history turns sent to API (2 turns = 1 exchange; older turns are dropped)
$config['gemini_max_history_turns'] = 6;

// Max requests per user session (soft safety cap — ~30 questions per login session)
$config['gemini_session_limit'] = 30;
