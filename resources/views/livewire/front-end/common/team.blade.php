<style>
    .team-card img {
        height: auto;
        /* Allow natural height */
        max-height: 300px;
        /* Set a maximum height */
        width: 100%;
        /* Keep image responsive */
        object-fit: contain;
        /* Prevent cropping */
        cursor: pointer;
        /* Show hand cursor on hover */
        transition: transform 0.2s ease-in-out;
        padding: 4px;
        /* Optional: adds some breathing room */
    }

    .team-card:hover img {
        transform: scale(1.05);
        /* Slight zoom on hover */
    }

    .team-modal-content {
        border-radius: 18px;
        padding: 25px;
        font-family: 'Segoe UI', 'Roboto', sans-serif;
    }

    /* .team-modal-img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #0b2c45;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
} */

    .team-modal-desc {
        font-size: 1.05rem;
        line-height: 1.7;
        max-width: 700px;
        margin: 0 auto;
        color: #4f4f4f;
    }

    .modal-header .modal-title {
        font-size: 1.5rem;
        color: #0b2c45;
    }

    .modal-header .btn-close {
        outline: none;
        box-shadow: none;
    }
</style>

<section style="background-color: #0b2c45" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 text-white">Meet Our Team</h2>

        <!-- First Row: Jotham & Anne Centered -->
        <div class="row justify-content-center mb-5">
            <!-- Dr. Jotham Mukundi -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Dr. Jotham Mukundi" data-title="Director & Chief Executive Officer"
                    data-img="../assets/images/frontend-pages/jotham-final.png"
                    data-desc="Dr. Jotham Gichuhi is a leadership and governance expert with 18+ years of experience. He holds a Ph.D. in Organizational Leadership, as well as certifications in CPA-K, CIA, CISA, and CHRP-K. He is the Group CEO of Tabor Training Institute and A&J Global Limited.">
                    <img src="../assets/images/frontend-pages/jotham-final.png" class="card-img-top"
                        alt="Dr. Jotham Mukundi">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Dr. Jotham Mukundi</h5>
                        <p class="card-text small">Director & Chief Executive Officer</p>
                    </div>
                </div>
            </div>

            <!-- Anne Ngumo -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Anne Ngumo" data-title="Director HR & Operations"
                    data-img="../assets/images/frontend-pages/Ann-final.png"
                    data-desc="Anne Ngumo holds an MBA in Human Resource Management and is a certified CHRP-K with over 10 years of experience in strategic HR management, recruitment, and organizational development. She is the Group Director HR and Operations for Tabor Training Institute, Global Institute of Strategic Governance, and A&J Global Limited">
                    <img src="../assets/images/frontend-pages/Ann-final.png" class="card-img-top" alt="Anne Ngumo">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Anne Ngumo</h5>
                        <p class="card-text small">Director HR & Operations</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- Jacklyne -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Jacklyne Nekesa Wangwe"
                    data-title="Lead Trainer – Caregiving and Health Services Support"
                    data-img="../assets/images/frontend-pages/Jacklyne.png"
                    data-desc="Jacklyne holds a Diploma in Kenya Registered Community Health Nursing and a Bachelor of Arts in Psychology and Counselling. She is a licensed psychologist who delivers expert instruction in caregiving and health service support, guided by a holistic and compassionate approach to wellness and training.">
                    <img src="../assets/images/frontend-pages/Jacklyne.png" class="card-img-top" alt="Jacklyne">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Jacklyne Nekesa Wangwe</h5>
                        <p class="card-text small">Lead Trainer Caregiving & Health Services Support</p>
                    </div>
                </div>
            </div>
            <!-- Slyvia -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Slyvia Njeri Kimani" data-title="Head of Training"
                    data-img="../assets/images/frontend-pages/Slyvia.png"
                    data-desc="Sylvia Njeri is the Head of Training at Tabor Training Institute, bringing a strong background in Hospitality and Tourism Management. She holds a Bachelor's degree in Hospitality and Tourism Management and is a certified Maritime Seafarer (STCW). Sylvia is also a professional coffee barista and a seasoned expert in pastry and bakery. With hands-on experience in both the culinary and maritime service industries, she is passionate about equipping students with practical skills that meet global standards.">
                    <img src="../assets/images/frontend-pages/Slyvia.png" class="card-img-top" alt="Slyvia">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Sylvia Njeri Kimani</h5>
                        <p class="card-text small">Head of Training</p>
                    </div>
                </div>
            </div>

            <!-- Felix -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Felix Wakhu Murule" data-title="Lead Trainer Orthopedic & Trauma Medicine"
                    data-img="../assets/images/frontend-pages/Felix.jpg"
                    data-desc="Felix is a highly experienced orthopedic and trauma technician with a strong clinical and academic foundation. He is a licensed Orthopedics and Trauma Medicine Practitioner by the Kenya Health Professions Oversight Authority (KHPOA), and brings hands-on expertise combined with a passion for teaching—equipping learners with practical skills and up-to-date knowledge in orthopedic and trauma care.">
                    <img src="../assets/images/frontend-pages/Felix.jpg" class="card-img-top" alt="Felix">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Felix Wakhu Murule</h5>
                        <p class="card-text small">Lead Trainer Orthopedic & Trauma Medicine</p>
                    </div>
                </div>
            </div>

            <!-- Victoria -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Victoria Wanjiku" data-title="Wellness Officer"
                    data-img="../assets/images/frontend-pages/Victoria.jpeg"
                    data-desc="Victoria holds a Bachelor’s degree in Counselling Psychology and is passionate about mental health and holistic student support. She fosters a nurturing environment that promotes emotional well-being, personal growth, and resilience among learners.">
                    <img src="../assets/images/frontend-pages/Victoria.jpeg" class="card-img-top" alt="Victoria">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Victoria Wanjiku</h5>
                        <p class="card-text small">Wellness Officer</p>
                    </div>
                </div>
            </div>

            <!-- Agnes -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Agnes Clare Mwangi" data-title="Lead Trainer, German Language"
                    data-img="../assets/images/frontend-pages/agnes.jpg"
                    data-desc="Agnes a C1-certified German Language trainer, brings vast training experience and a passion for helping learners achieve fluency for academic and professional success abroad.">
                    <img src="../assets/images/frontend-pages/agnes.jpg" class="card-img-top" alt="Agnes">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Agnes Clare Mwangi</h5>
                        <p class="card-text small">Lead Trainer, German Language</p>
                    </div>
                </div>
            </div>
            <!-- Marvin -->
            <div class="col-md-4">
                <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                    data-name="Marvin Kibiku Munyua" data-title="Lead Trainer, German Language"
                    data-img="../assets/images/frontend-pages/Marvin_Kibiku.png"
                    data-desc="Marvin is a dynamic German language expert with a passion for teaching. He empowers learners with practical communication skills and fosters a strong foundation in German language proficiency.">
                    <img src="../assets/images/frontend-pages/Marvin_Kibiku.png" class="card-img-top" alt="Marvin">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Marvin Kibiku Munyua</h5>
                        <p class="card-text small">Trainer, German Language</p>
                    </div>
                </div>
            </div>

            <!-- Principal -->
            {{-- <div class="col-md-4">
                <div class="card team-card text-dark text-center">
                    <!-- SVG Person Silhouette -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="250" fill="#ccc"
                        class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 10c1.105 0 2 .672 2 1.5v.5H3v-.5c0-.828.895-1.5 2-1.5h6z" />
                        <path fill-rule="evenodd" d="M8 9a3 3 0 100-6 3 3 0 000 6zm0 8A8 8 0 108 0a8 8 0 000 16z" />
                    </svg>

                    <div class="card-body">
                        <h5 class="card-title mb-1">Director - Nursing</h5>
                        <p class="card-text small">Director & College Principal</p>
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="teamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="teamModalImg" src="" class="mb-4 team-modal-img" style="max-height: 200px;"
                    alt="">
                <h5 class="modal-title fw-semibold text-center mb-2" id="teamModalLabel">Member Name</h5>
                <h6 class="text-primary fw-bold mb-2" id="teamModalTitle"></h6>
                <p class="text-muted lead team-modal-desc" id="teamModalDesc"></p>
            </div>
        </div>
    </div>
</div>


<script>
    document.querySelectorAll('.team-card').forEach(card => {
        card.addEventListener('click', function() {
            const name = this.dataset.name;
            const title = this.dataset.title;
            const img = this.dataset.img;
            const desc = this.dataset.desc;

            document.getElementById('teamModalLabel').innerText = name;
            document.getElementById('teamModalImg').src = img;
            document.getElementById('teamModalImg').alt = name;
            document.getElementById('teamModalTitle').innerText = title;
            document.getElementById('teamModalDesc').innerText = desc;
        });
    });
</script>
