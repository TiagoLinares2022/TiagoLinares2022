<?php include 'includes/header.php'; ?>

<section class="content-page">
    <div class="container">
        <h2>Fale Conosco</h2>
        <p>Preencha o formulário abaixo e entraremos em contato o mais breve possível.</p>
        <form action="processa_contato.php" method="POST" id="contact-form">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mensagem">Mensagem:</label>
                <textarea id="mensagem" name="mensagem" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn">Enviar Mensagem</button>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
