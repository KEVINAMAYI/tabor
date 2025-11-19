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

@php
    use Illuminate\Support\Str;

    $team = \App\Models\Team::all();

    // Detect "special" members using LIKE filtering
    $special = $team->filter(function ($t) {
        return Str::contains(strtolower($t->name), ['dr. jotham mukundi', 'anne ngumo']);
    });

    // All others
    $others = $team->reject(function ($t) use ($special) {
        return $special->contains('id', $t->id);
    });
@endphp

<section style="background-color: #0b2c45" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 text-white">Meet Our Team</h2>

        <!-- FIRST ROW: Special Members -->
        <div class="row justify-content-center mb-5">
            @foreach ($special as $t)
                <div class="col-md-4 mb-4">
                    <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                        data-name="{{ $t->name }}" data-title="{{ $t->title }}"
                        data-img="{{ asset('storage/' . $t->image) }}" data-desc="{{ $t->description }}">

                        <img src="{{ asset('storage/' . $t->image) }}" class="card-img-top">

                        <div class="card-body text-center">
                            <h5 class="card-title mb-1">{{ $t->name }}</h5>
                            <p class="card-text small">{{ $t->title }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- NORMAL GRID FOR ALL OTHER TEAM MEMBERS -->
        <div class="row">
            @foreach ($others as $t)
                <div class="col-md-4 mb-4">
                    <div class="card team-card text-dark" data-bs-toggle="modal" data-bs-target="#teamModal"
                        data-name="{{ $t->name }}" data-title="{{ $t->title }}"
                        data-img="{{ asset('storage/' . $t->image) }}" data-desc="{{ $t->description }}">

                        <img src="{{ asset('storage/' . $t->image) }}" class="card-img-top">

                        <div class="card-body text-center">
                            <h5 class="card-title mb-1">{{ $t->name }}</h5>
                            <p class="card-text small">{{ $t->title }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
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
