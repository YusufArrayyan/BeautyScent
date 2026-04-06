</main> <!-- Penutup tag main dari header.php -->

    <!-- ==========================================
         1. FLOATING CHAT BUTTON (ULTRA GLOW & MOBILE FIX)
    =========================================== -->
    <!-- PERBAIKAN: bottom-[85px] (naik) khusus di mobile agar tidak nyangkut di bottom navbar, md:bottom-8 untuk desktop -->
    <a href="https://wa.me/628123456789" target="_blank" class="fixed bottom-[85px] right-4 md:bottom-8 md:right-8 w-14 h-14 md:w-16 md:h-16 bg-gradient-to-br from-[#f472b6] to-[#e8a0bf] text-white rounded-full shadow-[0_10px_25px_rgba(244,114,182,0.5)] border-[3px] border-white flex items-center justify-center text-2xl md:text-3xl transition-all duration-500 hover:scale-110 hover:-translate-y-2 hover:rotate-12 hover:shadow-[0_20px_40px_rgba(244,114,182,0.7)] z-[90] group">
        <!-- Ping Effect on Hover -->
        <span class="absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-0 group-hover:opacity-40 group-hover:animate-ping transition-opacity duration-300"></span>
        <i class="fab fa-whatsapp relative z-10 transition-transform group-hover:scale-110"></i>
    </a>

    <!-- ==========================================
         2. ENTERPRISE MEGA FOOTER
    =========================================== -->
    <!-- PERBAIKAN: pb-24 di mobile agar copyright text tidak tertutup bottom navbar, md:pb-0 untuk desktop -->
    <footer class="bg-[#1c1114] text-gray-400 relative overflow-hidden mt-10 md:mt-16 mt-auto border-t border-white/5 font-sans pb-24 md:pb-0">
        
        <!-- 2.1 Abstract Gradient Background Decor -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#f472b6] via-[#e8a0bf] to-[#d4af37]"></div>
        <div class="absolute -top-[300px] -left-[300px] w-[600px] h-[600px] bg-gradient-to-br from-pink-900 to-[#1c1114] rounded-full blur-[100px] opacity-50 pointer-events-none"></div>
        <div class="absolute -bottom-[300px] -right-[300px] w-[600px] h-[600px] bg-gradient-to-tl from-yellow-700/10 to-transparent rounded-full blur-[120px] opacity-40 pointer-events-none"></div>

        <!-- 2.2 Top Footer Section (Newsletter & App Promo) -->
        <div class="border-b border-white/5 relative z-10 bg-white/5 backdrop-blur-sm">
            <div class="max-w-screen-xl mx-auto px-6 md:px-8 py-10 md:py-12 flex flex-col lg:flex-row justify-between items-center gap-10">
                
                <!-- Newsletter -->
                <div class="w-full lg:w-1/2">
                    <h3 class="text-xl md:text-2xl font-black font-serif text-white mb-3 tracking-tight flex items-center gap-3">
                        <i class="far fa-envelope text-pink-400"></i> Join the Beauty Insider
                    </h3>
                    <p class="text-sm font-medium text-gray-400 mb-6">Subscribe to receive updates, access to exclusive deals, and more.</p>
                    
                    <form action="" method="POST" class="relative max-w-md group flex" onsubmit="alert('Thank you for subscribing to BeautyScent!'); return false;">
                        <input type="email" name="email_newsletter" required placeholder="Enter your email address..." class="w-full bg-[#150d10] border border-white/20 py-3 md:py-4 pl-5 pr-32 rounded-full text-sm text-white focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all placeholder-gray-500">
                        <button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 bg-gradient-to-r from-[#f472b6] to-[#e8a0bf] hover:brightness-110 text-white px-5 md:px-6 rounded-full text-[11px] md:text-xs font-black uppercase tracking-widest transition-all shadow-md cursor-pointer z-10">Subscribe</button>
                    </form>
                </div>

                <!-- App Promo -->
                <div class="w-full lg:w-auto flex flex-col sm:flex-row items-center lg:items-start gap-6 lg:ml-auto text-center lg:text-left">
                    <div>
                        <h4 class="text-white font-black mb-1">Beauty On The Go</h4>
                        <p class="text-xs text-gray-400 font-medium">Coming Soon</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="#" onclick="alert('The BeautyScent iOS App is under development. Stay tuned!'); return false;" class="h-12 border border-white/20 rounded-xl px-4 flex items-center gap-3 hover:bg-white/10 hover:border-white/40 transition-all cursor-pointer">
                            <i class="fab fa-apple text-2xl text-white"></i>
                            <div class="text-left">
                                <p class="text-[8px] font-bold uppercase tracking-widest text-gray-400 leading-none">Download on the</p>
                                <p class="text-xs font-black text-white leading-tight">App Store</p>
                            </div>
                        </a>
                        <a href="#" onclick="alert('The BeautyScent Android App is under development. Stay tuned!'); return false;" class="h-12 border border-white/20 rounded-xl px-4 flex items-center gap-3 hover:bg-white/10 hover:border-white/40 transition-all cursor-pointer">
                            <i class="fab fa-google-play text-2xl text-white"></i>
                            <div class="text-left">
                                <p class="text-[8px] font-bold uppercase tracking-widest text-gray-400 leading-none">Get it on</p>
                                <p class="text-xs font-black text-white leading-tight">Google Play</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 2.3 Main Footer Links -->
        <div class="max-w-screen-xl mx-auto px-6 md:px-8 py-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-12 lg:gap-8 relative z-10">
            
            <!-- Branding (Lebih Lebar) -->
            <div class="sm:col-span-2 lg:col-span-4 pr-0 lg:pr-8">
                <!-- UPDATE LOGO FOOTER -->
                <a href="<?= $base_url ?>/index.php" class="shrink-0 group flex items-center gap-2 mb-6 w-max">
                    <img src="<?= $base_url ?>/assets/img/logo.png" alt="Logo" class="h-12 md:h-16 w-auto object-contain group-hover:scale-105 group-hover:rotate-6 transition-transform duration-300" onerror="this.style.display='none'; document.getElementById('fallback-icon-footer').classList.remove('hidden');">
                    
                    <div id="fallback-icon-footer" class="hidden bg-gradient-to-br from-[#f472b6] to-[#e8a0bf] p-2 rounded-xl text-white text-sm shadow-[0_4px_10px_rgba(244,114,182,0.3)]"><i class="fas fa-spa"></i></div>
                    
                    <div class="flex items-center">
                        <span class="text-2xl md:text-3xl font-black font-serif tracking-tighter text-pink-300 drop-shadow-sm">Beauty</span>
                        <span class="text-2xl md:text-3xl font-black font-serif tracking-tighter text-[#3a262a] ml-1 drop-shadow-sm">Scent</span>
                    </div>
                </a>

                <p class="text-sm leading-relaxed mb-8 font-medium text-gray-400">
                    The premier destination for luxury cosmetics, signature fragrances, and curated beauty. Embrace your inner elegance with authentic products.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#1877f2] hover:border-[#1877f2] hover:text-white hover:-translate-y-1 transition-all duration-300 shadow-sm"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://instagram.com/elokgalo.id" target="_blank" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-gradient-to-tr hover:from-yellow-400 hover:via-red-500 hover:to-purple-500 hover:border-transparent hover:text-white hover:-translate-y-1 transition-all duration-300 shadow-sm"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#1da1f2] hover:border-[#1da1f2] hover:text-white hover:-translate-y-1 transition-all duration-300 shadow-sm"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#ff0000] hover:border-[#ff0000] hover:text-white hover:-translate-y-1 transition-all duration-300 shadow-sm"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#0077b5] hover:border-[#0077b5] hover:text-white hover:-translate-y-1 transition-all duration-300 shadow-sm"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Kategori Jasa -->
            <div class="lg:col-span-3 lg:col-start-6">
                <h4 class="text-white font-black uppercase tracking-widest text-[11px] mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-4 bg-pink-400 rounded-sm"></span> Collections
                </h4>
                <ul class="space-y-4 text-sm font-semibold">
                    <li><a href="<?= $base_url ?>/kategori.php?k=bestseller" class="text-pink-400 hover:text-white hover:pl-2 transition-all flex items-center gap-2"><i class="fas fa-star text-yellow-400"></i> Best Sellers</a></li>
                    <li><a href="<?= $base_url ?>/kategori.php?k=perfume" class="hover:text-pink-300 hover:pl-2 transition-all">Signature Perfumes</a></li>
                    <li><a href="<?= $base_url ?>/kategori.php?k=skincare" class="hover:text-pink-300 hover:pl-2 transition-all">Luxury Skincare</a></li>
                    <li><a href="<?= $base_url ?>/kategori.php?k=makeup" class="hover:text-pink-300 hover:pl-2 transition-all">Premium Makeup</a></li>
                    <li><a href="<?= $base_url ?>/kategori.php?k=bodycare" class="hover:text-pink-300 hover:pl-2 transition-all">Body & Hair Care</a></li>
                </ul>
            </div>

            <!-- Perusahaan & Bantuan -->
            <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row gap-12 sm:gap-8 justify-between">
                <div class="flex-1">
                <div class="flex-1">
                    <h4 class="text-white font-black uppercase tracking-widest text-[11px] mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-gray-500 rounded-sm"></span> Company
                    </h4>
                    <ul class="space-y-4 text-sm font-semibold">
                        <li><a href="<?= $base_url ?>/halaman.php?p=The BeautyScent Story" class="hover:text-white hover:pl-2 transition-all">The BeautyScent Story</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Careers" class="text-pink-400 hover:text-pink-300 hover:pl-2 transition-all flex items-center gap-2">Careers <span class="bg-pink-400/20 border border-pink-400/30 text-pink-400 px-1.5 py-0.5 rounded text-[8px] uppercase font-black">We're Hiring</span></a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Beauty Blog %26 Tips" class="hover:text-white hover:pl-2 transition-all">Beauty Blog & Tips</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Partnerships %26 Brands" class="hover:text-white hover:pl-2 transition-all">Partnerships & Brands</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Seller Guide" class="hover:text-white hover:pl-2 transition-all">Seller Guide</a></li>
                    </ul>
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-black uppercase tracking-widest text-[11px] mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-gray-500 rounded-sm"></span> Help
                    </h4>
                    <ul class="space-y-4 text-sm font-semibold">
                        <li><a href="<?= $base_url ?>/halaman.php?p=Help Center (FAQ)" class="hover:text-white hover:pl-2 transition-all">Help Center (FAQ)</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=How to Order" class="hover:text-white hover:pl-2 transition-all">How to Order</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Terms %26 Conditions" class="hover:text-white hover:pl-2 transition-all">Terms & Conditions</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Privacy Policy" class="hover:text-white hover:pl-2 transition-all">Privacy Policy</a></li>
                        <li><a href="<?= $base_url ?>/halaman.php?p=Authenticity Guarantee" class="hover:text-white hover:pl-2 transition-all">Authenticity Guarantee</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- 2.4 Copyright Bottom Section -->
        <div class="border-t border-white/10 bg-[#120a0d] relative z-10">
            <div class="max-w-screen-xl mx-auto px-6 md:px-8 py-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-semibold text-gray-500">
                <div class="flex flex-col md:flex-row items-center gap-2 md:gap-4">
                    <p>&copy; <?= date('Y') ?> BeautyScent Inc.</p>
                    <span class="hidden md:block w-1 h-1 bg-gray-600 rounded-full"></span>
                    <p>All Rights Reserved.</p>
                </div>
                <p class="flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer">
                    Designed with <i class="fas fa-heart text-pink-400 animate-pulse mx-0.5"></i> by BeautyScent Team
                </p>
            </div>
        </div>
    </footer>

</body>
</html>