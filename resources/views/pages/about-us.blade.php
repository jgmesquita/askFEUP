@extends('layouts.app')

@section('content')
    <section class="aboutUsPage">
        <section class="intro">
            <div class="text-content">
                <h2>About Us</h2>
                <p>
                    Welcome to <strong>askFEUP</strong> — a collaborative online platform created specifically for the community at FEUP. Our mission is to connect students, foster collaboration, and provide a space for academic and social engagement. Whether you're looking for help with coursework, sharing experiences, or simply seeking advice, askFEUP is here for you.
                </p> 
            </div> 
            <span id="icons" class="material-symbols-outlined">person_play</span>
        </section> 

        <section class="functionalities">
            <span id="icons" class="material-symbols-outlined">question_mark</span>
            <div class="text-content">
                <h2> What can you do? </h2>
                <p>
                    askFEUP is a student-driven community designed to make sharing knowledge and experiences simple and intuitive. Think of it as your very own academic and social hub, where you can:
                </p>
                <ul>
                    <li><strong>Ask and Answer Questions:</strong> Post academic or general questions and get answers from fellow students.</li>
                    <li><strong>Vote and Rank Content:</strong> Use upvotes to highlight the most helpful and relevant responses.</li>
                    <li><strong>Earn Recognition:</strong> Build your reputation through contributions and helpful interactions within the community.</li>
                    <li><strong>Participate in Discussions:</strong> Comment on posts to dive deeper into topics and share insights.</li>
                    <li><strong>Follow Tags:</strong> Stay updated on subjects that interest you, from specific courses to general campus life.</li>
                </ul>
            </div> 
        </section> 

        <section class="whyUs">
            <h2>Why Choose askFEUP?</h2>
            <div class="text-content">
                <p>
                    Our platform is designed with students in mind. We prioritize:
                </p>
                <ul>
                    <li><strong>User-Friendliness:</strong> A clean, mobile-responsive interface makes it easy to interact with the community anywhere, anytime.</li>
                    <li><strong>Collaboration:</strong> askFEUP promotes teamwork by making it simple to share knowledge and learn from others.</li>
                    <li><strong>Privacy and Safety:</strong> Control your interactions with privacy-focused settings and content moderation tools.</li>
                </ul>
            </div> 
        </section> 

        <section class="ourVision">
            <div class="text-content">
                <p>
                    We envision a thriving community where every FEUP student feels empowered to participate, contribute, and grow. By fostering meaningful interactions, we aim to bridge gaps between students, create a culture of mutual support, and ensure that no one feels alone in their academic journey.
                </p>
            </div> 
            <h2>Our Vision</h2>
        </section> 

        <!-- Q&A Section -->
        <section class="qa">
            <h2>Frequently Asked Questions (FAQ)</h2>
            <div class="faq-item">
                <button class="faq-question">What is askFEUP?</button>
                <div class="faq-answer">
                    <p>askFEUP is a community-driven platform where students from FEUP can ask questions, share experiences, and engage with peers on academic and social topics.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">Who can join askFEUP?</button>
                <div class="faq-answer">
                    <p>askFEUP is designed for FEUP students, but anyone with an interest in collaborating with the FEUP community can join!</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">How do I earn badges?</button>
                <div class="faq-answer">
                    <p>You badges by participating in the community: asking questions, providing helpful answers, voting, and engaging in discussions.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">Is askFEUP free to use?</button>
                <div class="faq-answer">
                    <p>Yes, askFEUP is completely free for all FEUP students, faculty, and staff.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">Can I access askFEUP offline or without internet connectivity?</button>
                <div class="faq-answer">
                    <p>No, askFEUP requires an active internet connection to access content and interact with the community.</p>
                </div>
            </div>
        </section>

        <section class="joinUs">
            <div class="text-content">
            <h2>Join the Community</h2>
            <p>
                Becoming a part of askFEUP is easy and free. Sign up today and start asking, answering, and engaging with a supportive network of students. Together, let’s make learning and connecting at FEUP more enriching and enjoyable!
            </p>
            </div>
            <span id="icons" class="material-symbols-outlined">diversity_3</span>
         </section> 
    </section> 
@endsection
