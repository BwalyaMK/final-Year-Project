<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ScholarSense</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
                background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
                color: #1f2937;
                min-height: 100vh;
            }
            .container {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 1.5rem;
            }
            header {
                padding: 1rem 0;
            }
            .hero {
                text-align: center;
                padding: 6rem 1.5rem 4rem;
            }
            .logo {
                font-size: 3.75rem;
                font-weight: 600;
                color: #4f46e5;
                margin-bottom: 1.5rem;
                letter-spacing: -0.025em;
            }
            .tagline {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 1rem;
            }
            .description {
                font-size: 1.125rem;
                color: #6b7280;
                max-width: 42rem;
                margin: 0 auto 2.5rem;
                line-height: 1.75;
            }
            .cta-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 2rem;
                border-radius: 0.5rem;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.2s;
                border: 1px solid transparent;
            }
            .btn-primary {
                background: #4f46e5;
                color: white;
            }
            .btn-primary:hover {
                background: #4338ca;
                transform: scale(1.05);
            }
            .btn-secondary {
                background: white;
                color: #1f2937;
                border-color: #e5e7eb;
            }
            .btn-secondary:hover {
                border-color: #4f46e5;
                color: #4f46e5;
                transform: scale(1.05);
            }
            .features {
                padding: 4rem 0;
                background: white;
                border-top: 1px solid #e5e7eb;
            }
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 3rem;
                margin-top: 3rem;
            }
            .feature-card {
                text-align: center;
                padding: 2rem;
                border-radius: 0.75rem;
                transition: all 0.3s;
            }
            .feature-card:hover {
                background: #f9fafb;
                transform: translateY(-4px);
            }
            .feature-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            .feature-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: #4f46e5;
                margin-bottom: 0.75rem;
            }
            .feature-text {
                color: #6b7280;
                line-height: 1.625;
            }
            footer {
                padding: 3rem 0;
                text-align: center;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
                margin-top: 4rem;
            }
            .footer-text {
                font-style: italic;
                color: #6b7280;
                max-width: 48rem;
                margin: 0 auto;
                line-height: 1.75;
            }
            .laravel-badge {
                display: inline-block;
                margin-top: 1.5rem;
                padding: 0.5rem 1rem;
                background: white;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                color: #6b7280;
                border: 1px solid #e5e7eb;
            }
            .laravel-badge strong {
                color: #4f46e5;
            }
            @media (max-width: 768px) {
                .logo {
                    font-size: 2.5rem;
                }
                .tagline {
                    font-size: 1.25rem;
                }
                .description {
                    font-size: 1rem;
                }
                .hero {
                    padding: 3rem 1.5rem 2rem;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <!-- Clean header, no navigation for guests -->
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="container">
                    <h1 class="logo">ScholarSense</h1>
                    <p class="tagline">Smarter Research, Deeper Insights.</p>
                    <p class="description">
                        An AI-powered platform for semantic discovery, conceptual mapping, 
                        and intelligent summarization of academic resources.
                    </p>
                    <div class="cta-buttons">
                        <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary">Log In</a>
                    </div>
                </div>
            </section>

            <section class="features">
                <div class="container">
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">🔍</div>
                            <h3 class="feature-title">Semantic Search</h3>
                            <p class="feature-text">
                                Find what you mean, not just what you type. Our AI understands context and intent.
                            </p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">📚</div>
                            <h3 class="feature-title">Smart Summaries</h3>
                            <p class="feature-text">
                                Get concise, intelligent overviews of research materials in seconds.
                            </p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">🧠</div>
                            <h3 class="feature-title">Concept Maps</h3>
                            <p class="feature-text">
                                Visualize relationships between ideas and sources automatically.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <div class="container">
                <p class="footer-text">
                    Design and Development of a Context-Aware Automated Semantic and 
                    Conceptual Relationship Mapping System for Educational Research
                </p>
                <div class="laravel-badge">
                    Built with <strong>Laravel v{{ Illuminate\Foundation\Application::VERSION }}</strong>
                </div>
            </div>
        </footer>
    </body>
</html>