<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>R2K ERP — Business Management Software</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
/* ── TOKENS ──────────────────────────────────────────────────────────── */
:root{
    --pri:#696cff; --pri-dk:#4f52e8; --pri-glow:rgba(105,108,255,.22);
    --sec:#a855f7;
    --bg:#080c18; --bg2:#0c1220; --bg3:#111827; --bg4:#1a2235; --bg5:#222d42;
    --bdr:rgba(255,255,255,.07); --bdr2:rgba(255,255,255,.12);
    --tx:#e8edf5; --tx2:#8a96aa; --tx3:#4a5568;
    --green:#10b981; --amber:#f59e0b; --red:#ef4444; --blue:#3b82f6;
    --r:12px; --rl:20px; --rxl:28px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--tx);line-height:1.6;overflow-x:hidden}
a{color:inherit;text-decoration:none}
img{max-width:100%}
button{cursor:pointer;font-family:inherit}

/* ── BUTTONS ───────────────────────────────────────────────────────── */
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:8px;
     font-size:.9rem;font-weight:600;border:none;transition:all .2s;white-space:nowrap}
.btn-pri{background:var(--pri);color:#fff;box-shadow:0 4px 20px var(--pri-glow)}
.btn-pri:hover{background:var(--pri-dk);transform:translateY(-1px);box-shadow:0 8px 28px var(--pri-glow)}
.btn-ghost{background:transparent;color:var(--tx2);border:1px solid var(--bdr2)}
.btn-ghost:hover{color:var(--tx);border-color:rgba(255,255,255,.25)}
.btn-out{background:transparent;color:var(--tx);border:1.5px solid var(--bdr2)}
.btn-out:hover{background:rgba(255,255,255,.05)}
.btn-lg{padding:14px 32px;font-size:1rem}

/* ── NAVBAR ────────────────────────────────────────────────────────── */
.nav{position:fixed;top:0;left:0;right:0;z-index:200;height:64px;padding:0 6%;
     display:flex;align-items:center;justify-content:space-between;
     background:rgba(8,12,24,.82);backdrop-filter:blur(18px);
     border-bottom:1px solid var(--bdr);transition:all .3s}
.nav.scrolled{background:rgba(8,12,24,.97);box-shadow:0 4px 32px rgba(0,0,0,.4)}
.nav-logo{display:flex;align-items:center;gap:10px;font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:800}
.nav-mark{width:36px;height:36px;border-radius:10px;overflow:hidden;
          display:flex;align-items:center;justify-content:center}
.nav-mark img{width:36px;height:36px;object-fit:contain;border-radius:10px}
.nav-links{display:flex;gap:32px;list-style:none}
.nav-links a{font-size:.875rem;color:var(--tx2);font-weight:500;transition:color .2s}
.nav-links a:hover{color:var(--tx)}
.nav-ctas{display:flex;gap:10px}

/* ── HERO ──────────────────────────────────────────────────────────── */
.hero{min-height:100vh;padding:120px 6% 80px;display:flex;align-items:center;
      position:relative;overflow:hidden}
.hero-bg{position:absolute;inset:0;pointer-events:none}
.orb{position:absolute;border-radius:50%;filter:blur(80px);animation:drift 11s ease-in-out infinite}
.orb-1{width:620px;height:620px;top:-120px;right:-80px;
        background:radial-gradient(circle,rgba(105,108,255,.17) 0%,transparent 70%)}
.orb-2{width:500px;height:500px;bottom:-160px;left:-80px;animation-delay:-4s;
        background:radial-gradient(circle,rgba(168,85,247,.12) 0%,transparent 70%)}
.orb-3{width:320px;height:320px;top:40%;left:35%;animation-delay:-7s;
        background:radial-gradient(circle,rgba(59,130,246,.09) 0%,transparent 70%)}
@keyframes drift{
    0%,100%{transform:translate(0,0) scale(1)}
    33%    {transform:translate(28px,-28px) scale(1.07)}
    66%    {transform:translate(-18px,18px) scale(.94)}
}
.hero-grid{position:absolute;inset:0;pointer-events:none;
           background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),
                            linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);
           background-size:52px 52px;
           mask-image:radial-gradient(ellipse 80% 60% at 50% 50%,black 0%,transparent 100%)}
.hero-inner{position:relative;z-index:1;display:flex;align-items:center;gap:5%;
            width:100%;max-width:1280px;margin:0 auto}
.hero-text{flex:1 1 460px}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 14px 6px 8px;
            background:rgba(105,108,255,.1);border:1px solid rgba(105,108,255,.25);
            border-radius:30px;font-size:.78rem;font-weight:600;color:var(--pri);
            margin-bottom:24px;letter-spacing:.3px}
.badge-dot{width:6px;height:6px;border-radius:50%;background:var(--pri);animation:bpulse 2s ease infinite}
@keyframes bpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
.hero h1{font-family:'Outfit',sans-serif;font-size:clamp(2.5rem,4.8vw,4.2rem);
          font-weight:700;line-height:1.1;letter-spacing:-.5px;margin-bottom:20px;text-wrap:balance}
.hero h1 em{font-style:normal;
             background:linear-gradient(130deg,var(--pri) 0%,var(--sec) 100%);
             -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:1.05rem;color:var(--tx2);max-width:480px;line-height:1.75;margin-bottom:36px}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:44px}
.hero-proof{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.proof-item{display:flex;align-items:center;gap:7px;font-size:.8rem;color:var(--tx3)}
.proof-item i{color:var(--green);font-size:.95rem}

/* ── APP MOCKUP ────────────────────────────────────────────────────── */
.hero-visual{flex:1 1 520px;max-width:620px;position:relative}
.mock-wrap{perspective:1200px;transform:perspective(1200px) rotateY(-8deg) rotateX(4deg);
           transform-style:preserve-3d;transition:transform .6s ease}
.mock-wrap:hover{transform:perspective(1200px) rotateY(-3deg) rotateX(1deg)}
.mockup{background:var(--bg3);border-radius:14px;overflow:hidden;
        border:1px solid var(--bdr2);
        box-shadow:0 2px 4px rgba(0,0,0,.3),
                   0 48px 96px rgba(0,0,0,.55),
                   0 0 0 1px rgba(255,255,255,.05),
                   inset 0 1px 0 rgba(255,255,255,.07)}
.m-bar{height:40px;background:var(--bg2);border-bottom:1px solid var(--bdr);
       display:flex;align-items:center;padding:0 14px;gap:6px}
.m-dot{width:11px;height:11px;border-radius:50%}
.m-dot-r{background:#ef4444}
.m-dot-y{background:#f59e0b}
.m-dot-g{background:#10b981}
.m-bar-ttl{flex:1;text-align:center;font-size:.7rem;color:var(--tx3);font-weight:500;
           letter-spacing:.3px;margin-right:29px}
.m-body{display:flex;min-height:336px}
.m-side{width:52px;background:var(--bg2);border-right:1px solid var(--bdr);
        display:flex;flex-direction:column;align-items:center;padding:14px 0;gap:16px}
.m-logo{width:28px;height:28px;border-radius:7px;overflow:hidden;
        display:flex;align-items:center;justify-content:center;margin-bottom:6px}
.m-logo img{width:28px;height:28px;object-fit:contain;border-radius:7px}
.m-ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;
       justify-content:center;font-size:1rem;color:var(--tx3)}
.m-ico-a{background:rgba(105,108,255,.15);color:var(--pri)}
.m-main{flex:1;padding:14px;display:flex;flex-direction:column;gap:10px;overflow:hidden}
.m-hdr{display:flex;align-items:center;justify-content:space-between}
.m-pg-ttl{font-size:.82rem;font-weight:700;color:var(--tx)}
.m-btn-sm{display:flex;align-items:center;gap:4px;background:var(--pri);color:#fff;
          border-radius:5px;padding:4px 9px;font-size:.65rem;font-weight:600}
.m-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}
.m-st{background:var(--bg4);border:1px solid var(--bdr);border-radius:7px;padding:9px}
.m-st-lbl{font-size:.58rem;color:var(--tx3);margin-bottom:2px}
.m-st-val{font-size:.88rem;font-weight:700;color:var(--tx)}
.m-st-up{font-size:.58rem;font-weight:600;padding:1px 5px;border-radius:3px;
         display:inline-block;margin-top:2px;background:rgba(16,185,129,.14);color:var(--green)}
.m-st-dn{font-size:.58rem;font-weight:600;padding:1px 5px;border-radius:3px;
         display:inline-block;margin-top:2px;background:rgba(239,68,68,.14);color:var(--red)}
.m-thead{display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr;padding:5px 7px;
         font-size:.57rem;font-weight:600;color:var(--tx3);text-transform:uppercase;
         letter-spacing:.5px;border-bottom:1px solid var(--bdr)}
.m-row{display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr;padding:7px;
       font-size:.66rem;color:var(--tx2);border-bottom:1px solid var(--bdr);align-items:center}
.m-row:last-child{border-bottom:none}
.m-id{color:var(--pri);font-weight:600}
.m-bdg{display:inline-block;padding:1px 6px;border-radius:3px;font-size:.58rem;font-weight:600}
.m-bdg-p{background:rgba(16,185,129,.14);color:var(--green)}
.m-bdg-w{background:rgba(245,158,11,.14);color:var(--amber)}
.m-bdg-o{background:rgba(239,68,68,.14);color:var(--red)}
.mock-glow{position:absolute;bottom:-60px;left:5%;right:5%;height:180px;
           background:radial-gradient(ellipse,rgba(105,108,255,.22) 0%,transparent 70%);
           filter:blur(24px);pointer-events:none;z-index:-1}

/* ── TRUST ─────────────────────────────────────────────────────────── */
.trust{padding:20px 6%;background:var(--bg2);border-top:1px solid var(--bdr);border-bottom:1px solid var(--bdr)}
.trust-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:28px;flex-wrap:wrap;justify-content:center}
.trust-lbl{font-size:.72rem;color:var(--tx3);white-space:nowrap;letter-spacing:.8px;text-transform:uppercase;font-weight:600}
.trust-line{width:1px;height:22px;background:var(--bdr2)}
.trust-chips{display:flex;gap:10px;flex-wrap:wrap}
.trust-chip{padding:5px 14px;border-radius:30px;background:var(--bg4);border:1px solid var(--bdr2);
            font-size:.78rem;font-weight:600;color:var(--tx2)}

/* ── STATS ─────────────────────────────────────────────────────────── */
.stats-wrap{padding:0 6%;margin:64px 0}
.stats-band{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;
            background:var(--bdr);border:1px solid var(--bdr);border-radius:var(--rl);
            overflow:hidden;max-width:1280px;margin:0 auto}
.stat-cell{background:var(--bg3);padding:36px 28px;text-align:center}
.stat-n{font-family:'Outfit',sans-serif;font-size:2.8rem;font-weight:800;
        color:var(--tx);line-height:1;margin-bottom:6px}
.stat-n-suf{color:var(--pri)}
.stat-l{font-size:.78rem;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;font-weight:600}

/* ── SECTIONS ──────────────────────────────────────────────────────── */
.section{padding:96px 6%}
.sec-center{text-align:center;max-width:640px;margin:0 auto 64px}
.sec-eye{font-size:.7rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;
          color:var(--pri);margin-bottom:12px;
          display:flex;align-items:center;justify-content:center;gap:8px}
.sec-eye::before,.sec-eye::after{content:'';flex:1;max-width:28px;height:1px;background:var(--pri)}
.sec-title{font-family:'Outfit',sans-serif;font-size:clamp(1.9rem,3.2vw,2.8rem);
           font-weight:700;letter-spacing:-.3px;margin-bottom:16px;text-wrap:balance}
.sec-sub{font-size:.95rem;color:var(--tx2);line-height:1.75}

/* ── FEATURES ──────────────────────────────────────────────────────── */
.feat-section{padding:80px 6%}
.feat-blk{max-width:1280px;margin:0 auto 80px;display:flex;align-items:center;gap:6%}
.feat-blk-flip{flex-direction:row-reverse}
.feat-txt{flex:1 1 380px}
.feat-eye{font-size:.7rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;
           color:var(--pri);margin-bottom:12px}
.feat-h{font-family:'Outfit',sans-serif;font-size:clamp(1.6rem,2.5vw,2.2rem);
         font-weight:700;letter-spacing:-.2px;margin-bottom:16px;text-wrap:balance}
.feat-p{font-size:.93rem;color:var(--tx2);line-height:1.75;margin-bottom:22px}
.feat-chk{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:28px}
.feat-chk li{display:flex;align-items:flex-start;gap:10px;font-size:.87rem;color:var(--tx2)}
.feat-chk li i{color:var(--pri);font-size:1rem;margin-top:2px;flex-shrink:0}
.feat-vis{flex:1 1 420px;max-width:520px;background:var(--bg3);border:1px solid var(--bdr2);
          border-radius:var(--rl);padding:28px;position:relative;overflow:hidden}
.feat-vis::before{content:'';position:absolute;top:-50px;right:-50px;width:220px;height:220px;
                  border-radius:50%;background:radial-gradient(circle,var(--pri-glow) 0%,transparent 70%)}
.feat-ico-lg{width:56px;height:56px;border-radius:14px;background:rgba(105,108,255,.1);
             border:1px solid rgba(105,108,255,.18);display:flex;align-items:center;
             justify-content:center;font-size:1.7rem;color:var(--pri);margin-bottom:18px}
.feat-vis-ttl{font-size:.95rem;font-weight:700;margin-bottom:14px;color:var(--tx)}
.feat-mini{list-style:none;display:flex;flex-direction:column;gap:8px}
.feat-mini li{display:flex;align-items:center;gap:10px;padding:9px 12px;
              background:var(--bg4);border-radius:8px;font-size:.8rem;color:var(--tx2)}
.feat-mini li i{color:var(--pri);font-size:.95rem;flex-shrink:0}
.feat-mini-tag{margin-left:auto;font-size:.66rem;font-weight:600;padding:2px 7px;border-radius:4px}
.tag-gst{background:rgba(16,185,129,.14);color:var(--green)}
.tag-new{background:rgba(105,108,255,.14);color:var(--pri)}
.tag-auto{background:rgba(245,158,11,.14);color:var(--amber)}

/* ── MODULES ───────────────────────────────────────────────────────── */
.mods-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(192px,1fr));gap:13px;
           max-width:1280px;margin:0 auto}
.mod-card{background:var(--bg3);border:1px solid var(--bdr);border-radius:var(--r);
          padding:22px 18px;cursor:default;transition:all .3s;position:relative;overflow:hidden}
.mod-card::after{content:'';position:absolute;inset:0;border-radius:var(--r);
                 background:radial-gradient(circle at 50% 0%,rgba(105,108,255,.12) 0%,transparent 60%);
                 opacity:0;transition:opacity .3s}
.mod-card:hover{border-color:rgba(105,108,255,.35);transform:translateY(-4px);
                box-shadow:0 14px 36px rgba(105,108,255,.12),0 2px 8px rgba(0,0,0,.3)}
.mod-card:hover::after{opacity:1}
.mod-ico{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;
         justify-content:center;font-size:1.3rem;margin-bottom:14px;position:relative;z-index:1}
.ico-pri{background:rgba(105,108,255,.12);color:var(--pri)}
.ico-grn{background:rgba(16,185,129,.12);color:var(--green)}
.ico-amb{background:rgba(245,158,11,.12);color:var(--amber)}
.ico-red{background:rgba(239,68,68,.12);color:var(--red)}
.ico-blu{background:rgba(59,130,246,.12);color:var(--blue)}
.ico-pur{background:rgba(139,92,246,.12);color:#8b5cf6}
.mod-nm{font-size:.88rem;font-weight:700;color:var(--tx);margin-bottom:6px;position:relative;z-index:1}
.mod-ds{font-size:.74rem;color:var(--tx3);line-height:1.55;position:relative;z-index:1}

/* ── PROCESS ───────────────────────────────────────────────────────── */
.proc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--bdr);
           border:1px solid var(--bdr);border-radius:var(--rxl);overflow:hidden;
           max-width:1100px;margin:0 auto}
.proc-step{background:var(--bg3);padding:40px 32px;position:relative;display:flex;flex-direction:column;gap:16px}
.proc-n{font-family:'Outfit',sans-serif;font-size:4rem;font-weight:800;line-height:1;
         color:rgba(105,108,255,.12);position:absolute;top:20px;right:24px}
.proc-ico{width:52px;height:52px;border-radius:14px;background:rgba(105,108,255,.1);
          display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--pri)}
.proc-ttl{font-size:1.05rem;font-weight:700;color:var(--tx)}
.proc-p{font-size:.84rem;color:var(--tx2);line-height:1.65}

/* ── PRICING ───────────────────────────────────────────────────────── */
.pricing-tog{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:48px}
.tog-lbl{font-size:.9rem;font-weight:600;color:var(--tx2);transition:color .2s}
.tog-lbl.act{color:var(--tx)}
.tog-sw{width:52px;height:28px;background:var(--bg5);border-radius:14px;
        position:relative;cursor:pointer;border:none;outline:none;transition:background .25s}
.tog-sw.on{background:var(--pri)}
.tog-knob{position:absolute;top:3px;left:3px;width:22px;height:22px;border-radius:50%;
          background:#fff;transition:transform .25s}
.tog-sw.on .tog-knob{transform:translateX(24px)}
.save-pill{background:rgba(16,185,129,.11);border:1px solid rgba(16,185,129,.22);
           color:var(--green);font-size:.7rem;font-weight:700;padding:3px 10px;
           border-radius:20px;letter-spacing:.3px}
.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:20px;
            align-items:start;max-width:1280px;margin:0 auto}
.plan-card{background:var(--bg3);border:1.5px solid var(--bdr);border-radius:var(--rxl);
           padding:32px 28px;position:relative;transition:all .3s}
.plan-card:hover{transform:translateY(-4px);box-shadow:0 16px 48px rgba(0,0,0,.3)}
.plan-card.pop{border-color:var(--pri);background:linear-gradient(160deg,rgba(105,108,255,.08),var(--bg3))}
.plan-card.pop::before{content:'Most Popular';position:absolute;top:-13px;left:50%;
                        transform:translateX(-50%);
                        background:linear-gradient(90deg,var(--pri),var(--sec));
                        color:#fff;font-size:.68rem;font-weight:700;padding:4px 16px;
                        border-radius:20px;white-space:nowrap;text-transform:uppercase;letter-spacing:.5px}
.plan-cyc{display:inline-block;font-size:.68rem;font-weight:700;padding:3px 10px;
          border-radius:20px;margin-bottom:12px;background:rgba(105,108,255,.1);
          color:var(--pri);letter-spacing:.5px;text-transform:uppercase}
.plan-nm{font-family:'Outfit',sans-serif;font-size:1.3rem;font-weight:800;color:var(--tx);margin-bottom:6px}
.plan-tag{font-size:.78rem;color:var(--tx3);margin-bottom:20px}
.plan-pr{display:flex;align-items:flex-end;gap:3px;margin-bottom:6px}
.plan-pr-cur{font-size:1.1rem;font-weight:700;color:var(--tx);padding-bottom:8px}
.plan-pr-amt{font-family:'Outfit',sans-serif;font-size:3.2rem;font-weight:800;color:var(--tx);line-height:1}
.plan-pr-per{font-size:.78rem;color:var(--tx3);padding-bottom:7px}
.plan-note{font-size:.73rem;color:var(--tx3);margin-bottom:20px}
.plan-lims{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:20px}
.plan-lim{display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--tx2);
          background:var(--bg4);border:1px solid var(--bdr);border-radius:6px;padding:5px 10px}
.plan-lim i{color:var(--pri);font-size:.88rem}
.plan-div{height:1px;background:var(--bdr);margin:20px 0}
.plan-mods{list-style:none;display:flex;flex-direction:column;gap:9px;margin-bottom:28px}
.plan-mods li{display:flex;align-items:center;gap:9px;font-size:.82rem;color:var(--tx2)}
.plan-mods li i{color:var(--green);font-size:.95rem;flex-shrink:0}
.plan-mods .more{color:var(--tx3);font-size:.74rem;margin-top:2px}
.plan-btn{width:100%;padding:13px;border-radius:8px;font-size:.9rem;font-weight:700;
          border:none;cursor:pointer;transition:all .2s;text-align:center;display:block}
.p-btn-solid{background:var(--pri);color:#fff}
.p-btn-solid:hover{background:var(--pri-dk)}
.p-btn-out{background:transparent;color:var(--tx);border:1.5px solid var(--bdr2)}
.p-btn-out:hover{background:rgba(255,255,255,.05)}
.p-btn-free{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.p-btn-free:hover{background:rgba(16,185,129,.18)}
.cyc-blk{display:none}
.cyc-blk.vis{display:contents}
.no-plans{text-align:center;padding:60px 24px;color:var(--tx3)}
.no-plans i{font-size:3rem;display:block;margin-bottom:12px}

/* ── TESTIMONIALS ──────────────────────────────────────────────────── */
.testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));
            gap:20px;max-width:1100px;margin:0 auto}
.testi-card{background:var(--bg3);border:1px solid var(--bdr);border-radius:var(--rl);
            padding:28px 26px;display:flex;flex-direction:column;gap:16px;transition:all .25s}
.testi-card:hover{border-color:var(--bdr2);box-shadow:0 8px 28px rgba(0,0,0,.25)}
.testi-stars{display:flex;gap:4px;color:var(--amber);font-size:.9rem}
.testi-q{font-size:.88rem;color:var(--tx2);line-height:1.7;flex:1}
.testi-q::before{content:'\201C';font-size:1.5rem;line-height:0;vertical-align:-.35em;color:var(--pri);margin-right:2px}
.testi-author{display:flex;align-items:center;gap:12px}
.testi-av{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;
          font-size:.9rem;font-weight:700;color:#fff;flex-shrink:0}
.av-a{background:linear-gradient(135deg,var(--pri),var(--sec))}
.av-b{background:linear-gradient(135deg,var(--green),#065f46)}
.av-c{background:linear-gradient(135deg,var(--amber),#b45309)}
.testi-nm{font-size:.84rem;font-weight:700;color:var(--tx)}
.testi-role{font-size:.74rem;color:var(--tx3)}

/* ── CTA BAND ──────────────────────────────────────────────────────── */
.cta-band{margin:0 6%;border-radius:var(--rxl);position:relative;overflow:hidden;
          background:linear-gradient(135deg,rgba(105,108,255,.14) 0%,rgba(168,85,247,.14) 100%);
          border:1px solid rgba(105,108,255,.22);padding:80px 8%;text-align:center}
.cta-band::before{content:'';position:absolute;inset:0;
    background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),
                     linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);
    background-size:40px 40px}
.cta-orb1{position:absolute;top:-80px;left:-80px;width:400px;height:400px;border-radius:50%;
           background:radial-gradient(circle,rgba(105,108,255,.15) 0%,transparent 70%);
           filter:blur(40px);pointer-events:none}
.cta-orb2{position:absolute;bottom:-80px;right:-80px;width:400px;height:400px;border-radius:50%;
           background:radial-gradient(circle,rgba(168,85,247,.12) 0%,transparent 70%);
           filter:blur(40px);pointer-events:none}
.cta-ttl{font-family:'Outfit',sans-serif;font-size:clamp(1.9rem,3.8vw,3rem);font-weight:700;
          margin-bottom:16px;position:relative}
.cta-sub{font-size:1rem;color:var(--tx2);margin-bottom:36px;position:relative}
.cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;position:relative}

/* ── FOOTER ────────────────────────────────────────────────────────── */
.footer{padding:64px 6% 40px;border-top:1px solid var(--bdr);margin-top:80px}
.foot-top{display:grid;grid-template-columns:1.6fr repeat(3,1fr);gap:48px;margin-bottom:48px}
.foot-brand-copy{font-size:.84rem;color:var(--tx3);line-height:1.7;max-width:250px;margin-top:12px}
.foot-col-ttl{font-size:.76rem;font-weight:700;color:var(--tx);text-transform:uppercase;
               letter-spacing:1px;margin-bottom:16px}
.foot-links{list-style:none;display:flex;flex-direction:column;gap:10px}
.foot-links a{font-size:.82rem;color:var(--tx3);transition:color .2s}
.foot-links a:hover{color:var(--tx)}
.foot-btm{border-top:1px solid var(--bdr);padding-top:28px;
          display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.foot-copy{font-size:.76rem;color:var(--tx3)}
.foot-badges{display:flex;gap:10px;flex-wrap:wrap}
.foot-bdg{display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:6px;
          background:var(--bg3);border:1px solid var(--bdr);font-size:.7rem;color:var(--tx3)}
.foot-bdg i{color:var(--green);font-size:.88rem}

/* ── REVEAL ANIMATIONS ─────────────────────────────────────────────── */
.rv,.rv-l,.rv-r,.rv-s{opacity:0;transition:opacity .65s ease,transform .65s ease}
.rv   {transform:translateY(30px)}
.rv-l {transform:translateX(-40px)}
.rv-r {transform:translateX(40px)}
.rv-s {transform:scale(.94)}
.rv.vis,.rv-l.vis,.rv-r.vis,.rv-s.vis{opacity:1;transform:none}
.d1{transition-delay:.1s}
.d2{transition-delay:.2s}
.d3{transition-delay:.3s}
.d4{transition-delay:.4s}

/* ── UTILITIES ─────────────────────────────────────────────────────── */
.sec-alt{background:var(--bg2)}
.link-pri{color:var(--pri)}
.foot-logo{margin-bottom:14px}

/* ── RESPONSIVE ────────────────────────────────────────────────────── */
@media(max-width:940px){
    .hero-inner{flex-direction:column;text-align:center}
    .hero-sub,.hero-ctas,.hero-proof{justify-content:center}
    .hero-visual{width:100%;max-width:480px}
    .feat-blk,.feat-blk-flip{flex-direction:column}
    .feat-vis{max-width:100%}
    .stats-band{grid-template-columns:repeat(2,1fr)}
    .proc-grid{grid-template-columns:1fr}
    .foot-top{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
    .nav-links{display:none}
    .mock-wrap{transform:none}
    .cta-band{margin:0;border-radius:0}
    .foot-top{grid-template-columns:1fr}
}
</style>
</head>
<body>

<?php
$_su  = $signupUrl ?? '#';
$_lu  = $loginUrl  ?? '#';
$_cur = '₹';
$_p   = $plansByCycle  ?? [];
$_pm  = $planModules   ?? [];
$_hM  = !empty($_p['monthly']);
$_hY  = !empty($_p['yearly']);
$_hF  = !empty($_p['free']);
$_any = !empty($_p);
$_popIdx  = 1;

/* Calculate actual savings % by matching monthly vs yearly plans positionally */
$_savePct = 0;
if ($_hM && $_hY) {
    $mPlans = array_values($_p['monthly']);
    $yPlans = array_values($_p['yearly']);
    $pairs  = min(count($mPlans), count($yPlans));
    $total  = 0;
    $count  = 0;
    for ($i = 0; $i < $pairs; $i++) {
        $mAnnual = (float)($mPlans[$i]->TotalAmount ?: $mPlans[$i]->Price ?: 0) * 12;
        $yCost   = (float)($yPlans[$i]->TotalAmount ?: $yPlans[$i]->Price ?: 0);
        if ($mAnnual > 0 && $yCost < $mAnnual) {
            $total += (($mAnnual - $yCost) / $mAnnual) * 100;
            $count++;
        }
    }
    if ($count > 0) {
        $_savePct = (int)round($total / $count);
    }
}
?>

<!-- ── NAVBAR ── -->
<nav class="nav" id="mainNav">
    <div class="nav-logo">
        <div class="nav-mark"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K ERP"></div>
        <span>R2K ERP</span>
    </div>
    <ul class="nav-links">
        <li><a href="#features">Features</a></li>
        <li><a href="#modules">Modules</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#how">How it works</a></li>
    </ul>
    <div class="nav-ctas">
        <a href="<?= htmlspecialchars($_lu) ?>" class="btn btn-ghost">Sign In</a>
        <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Start Free Trial</a>
    </div>
</nav>

<!-- ── HERO ── -->
<section class="hero">
    <div class="hero-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="hero-grid"></div>
    <div class="hero-inner">
        <!-- Text -->
        <div class="hero-text">
            <div class="hero-badge"><span class="badge-dot"></span>Complete Business ERP for India</div>
            <h1>Manage Your<br>Business with<br><em>Confidence</em></h1>
            <p class="hero-sub">
                From invoicing to payroll, from inventory to accounting —
                every tool your growing business needs, unified in one
                GST-compliant cloud platform.
            </p>
            <div class="hero-ctas">
                <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri btn-lg">
                    <i class="bx bx-rocket"></i> Start Free Trial
                </a>
                <a href="#pricing" class="btn btn-out btn-lg">
                    <i class="bx bx-tag"></i> See Pricing
                </a>
            </div>
            <div class="hero-proof">
                <div class="proof-item"><i class="bx bx-check-circle"></i> No credit card required</div>
                <div class="proof-item"><i class="bx bx-check-circle"></i> GST compliant</div>
                <div class="proof-item"><i class="bx bx-check-circle"></i> Setup in minutes</div>
            </div>
        </div>
        <!-- App mockup -->
        <div class="hero-visual">
            <div class="mock-wrap">
                <div class="mockup">
                    <div class="m-bar">
                        <div class="m-dot m-dot-r"></div>
                        <div class="m-dot m-dot-y"></div>
                        <div class="m-dot m-dot-g"></div>
                        <div class="m-bar-ttl">R2K ERP &nbsp;·&nbsp; Invoices</div>
                    </div>
                    <div class="m-body">
                        <div class="m-side">
                            <div class="m-logo"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K"></div>
                            <div class="m-ico m-ico-a"><i class="bx bx-receipt"></i></div>
                            <div class="m-ico"><i class="bx bx-box"></i></div>
                            <div class="m-ico"><i class="bx bx-group"></i></div>
                            <div class="m-ico"><i class="bx bx-bar-chart-alt-2"></i></div>
                            <div class="m-ico"><i class="bx bx-cog"></i></div>
                        </div>
                        <div class="m-main">
                            <div class="m-hdr">
                                <span class="m-pg-ttl">Invoices</span>
                                <span class="m-btn-sm"><i class="bx bx-plus"></i> New Invoice</span>
                            </div>
                            <div class="m-stats">
                                <div class="m-st">
                                    <div class="m-st-lbl">This Month</div>
                                    <div class="m-st-val">₹4.2L</div>
                                    <div class="m-st-up">+18%</div>
                                </div>
                                <div class="m-st">
                                    <div class="m-st-lbl">Pending</div>
                                    <div class="m-st-val">₹86K</div>
                                    <div class="m-st-dn">3 due</div>
                                </div>
                                <div class="m-st">
                                    <div class="m-st-lbl">Collected</div>
                                    <div class="m-st-val">₹3.3L</div>
                                    <div class="m-st-up">94%</div>
                                </div>
                            </div>
                            <div class="m-thead">
                                <div>Invoice</div><div>Customer</div>
                                <div>Amount</div><div>Status</div>
                            </div>
                            <div class="m-row"><div class="m-id">#INV-0091</div><div>Lakshmi Traders</div><div>₹18,400</div><div><span class="m-bdg m-bdg-p">Paid</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0090</div><div>Sun Industries</div><div>₹42,200</div><div><span class="m-bdg m-bdg-w">Pending</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0089</div><div>Nova Tech</div><div>₹9,850</div><div><span class="m-bdg m-bdg-p">Paid</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0088</div><div>Ravi &amp; Sons</div><div>₹26,000</div><div><span class="m-bdg m-bdg-o">Overdue</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mock-glow"></div>
        </div>
    </div>
</section>

<!-- ── TRUST ── -->
<div class="trust">
    <div class="trust-inner">
        <span class="trust-lbl">Trusted by businesses across India</span>
        <div class="trust-line"></div>
        <div class="trust-chips">
            <span class="trust-chip">Retail</span>
            <span class="trust-chip">Manufacturing</span>
            <span class="trust-chip">Trading</span>
            <span class="trust-chip">Services</span>
            <span class="trust-chip">Distribution</span>
        </div>
    </div>
</div>

<!-- ── STATS ── -->
<div class="stats-wrap">
    <div class="stats-band rv">
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="15">0</span><span class="stat-n-suf">+</span></div>
            <div class="stat-l">ERP Modules</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="100">0</span><span class="stat-n-suf">%</span></div>
            <div class="stat-l">Cloud Based</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="stat-n-suf">₹0</span></div>
            <div class="stat-l">Setup Cost</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="24">0</span><span class="stat-n-suf">/7</span></div>
            <div class="stat-l">Support</div>
        </div>
    </div>
</div>

<!-- ── FEATURES ── -->
<div class="feat-section" id="features">

    <!-- Block 1: Invoicing -->
    <div class="feat-blk rv-l">
        <div class="feat-txt">
            <div class="feat-eye">Sales &amp; Invoicing</div>
            <h2 class="feat-h">Create invoices in seconds, get paid faster</h2>
            <p class="feat-p">Generate GST-compliant invoices, track payment status in real time, and follow up on overdue receivables — all from one screen.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Auto-calculate CGST, SGST &amp; IGST based on party state</li>
                <li><i class="bx bx-check"></i>Convert quotations and orders to invoices in one click</li>
                <li><i class="bx bx-check"></i>Attach files, record payments, and send PDF via email</li>
                <li><i class="bx bx-check"></i>Customer-wise outstanding and aging reports</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-r">
            <div class="feat-ico-lg"><i class="bx bx-receipt"></i></div>
            <div class="feat-vis-ttl">Sales Workflow</div>
            <ul class="feat-mini">
                <li><i class="bx bx-file"></i>Quotation <span class="feat-mini-tag tag-new">Draft</span></li>
                <li><i class="bx bx-store"></i>Sales Order <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-receipt"></i>Invoice <span class="feat-mini-tag tag-gst">GST</span></li>
                <li><i class="bx bx-revision"></i>Credit Note <span class="feat-mini-tag tag-auto">Returns</span></li>
                <li><i class="bx bx-money"></i>Payment Recording</li>
            </ul>
        </div>
    </div>

    <!-- Block 2: Inventory -->
    <div class="feat-blk feat-blk-flip rv-r">
        <div class="feat-txt">
            <div class="feat-eye">Inventory Management</div>
            <h2 class="feat-h">Never run out of stock again</h2>
            <p class="feat-p">Track every item across multiple branches in real time. Set reorder alerts, manage batches, and get stock valuation reports instantly.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Real-time stock levels across all branches</li>
                <li><i class="bx bx-check"></i>Reorder point alerts and low-stock notifications</li>
                <li><i class="bx bx-check"></i>Automatic stock updates on every transaction</li>
                <li><i class="bx bx-check"></i>Product-wise profit margin tracking</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-l">
            <div class="feat-ico-lg"><i class="bx bx-package"></i></div>
            <div class="feat-vis-ttl">Stock at a Glance</div>
            <ul class="feat-mini">
                <li><i class="bx bx-box"></i>Product Catalog &amp; Categories</li>
                <li><i class="bx bx-transfer"></i>Purchase &amp; GRN Tracking <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-trending-down"></i>Low Stock Alerts <span class="feat-mini-tag tag-new">Smart</span></li>
                <li><i class="bx bx-building-house"></i>Multi-Branch Stock View</li>
                <li><i class="bx bx-bar-chart-alt-2"></i>Stock Valuation Report</li>
            </ul>
        </div>
    </div>

    <!-- Block 3: Accounting -->
    <div class="feat-blk rv-l">
        <div class="feat-txt">
            <div class="feat-eye">Accounting &amp; Finance</div>
            <h2 class="feat-h">Full accounting built right in</h2>
            <p class="feat-p">Double-entry accounting, bank reconciliation, P&amp;L, and balance sheet — no external accountant software needed.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Manual journal entries with multi-ledger support</li>
                <li><i class="bx bx-check"></i>Bank reconciliation with statement upload</li>
                <li><i class="bx bx-check"></i>Profit &amp; Loss, Balance Sheet, Cash Flow</li>
                <li><i class="bx bx-check"></i>Recurring journal automation</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-r">
            <div class="feat-ico-lg"><i class="bx bx-line-chart"></i></div>
            <div class="feat-vis-ttl">Finance Reports</div>
            <ul class="feat-mini">
                <li><i class="bx bx-book-open"></i>Day Book &amp; Ledgers <span class="feat-mini-tag tag-gst">GST</span></li>
                <li><i class="bx bx-building-house"></i>Bank Reconciliation <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-trending-up"></i>Profit &amp; Loss Statement</li>
                <li><i class="bx bx-stats"></i>Balance Sheet</li>
                <li><i class="bx bx-transfer-alt"></i>Cash Flow Report</li>
            </ul>
        </div>
    </div>
</div>

<!-- ── MODULES ── -->
<section class="section sec-alt" id="modules">
    <div class="sec-center rv">
        <div class="sec-eye">Everything included</div>
        <h2 class="sec-title">15+ modules. One subscription.</h2>
        <p class="sec-sub">From first quotation to final report — every business process covered.</p>
    </div>
    <div class="mods-grid">
        <div class="mod-card rv d1"><div class="mod-ico ico-pri"><i class="bx bx-receipt"></i></div><div class="mod-nm">Invoicing</div><div class="mod-ds">GST-compliant invoices with payment tracking.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-amb"><i class="bx bx-file"></i></div><div class="mod-nm">Quotations</div><div class="mod-ds">Professional quotes, convert to order in one click.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-grn"><i class="bx bx-store"></i></div><div class="mod-nm">Sales Orders</div><div class="mod-ds">Full order lifecycle from confirmation to delivery.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-blu"><i class="bx bx-cart"></i></div><div class="mod-nm">Purchase Orders</div><div class="mod-ds">Raise POs, track vendor deliveries easily.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-pur"><i class="bx bx-package"></i></div><div class="mod-nm">Purchase &amp; GRN</div><div class="mod-ds">Record purchases and goods receipt notes.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-red"><i class="bx bx-revision"></i></div><div class="mod-nm">Sales Returns</div><div class="mod-ds">Credit notes and returns with stock update.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-amb"><i class="bx bx-transfer"></i></div><div class="mod-nm">Purchase Returns</div><div class="mod-ds">Return goods to suppliers, track debit notes.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-grn"><i class="bx bx-user-circle"></i></div><div class="mod-nm">Customers</div><div class="mod-ds">Profiles, balances, history and groups.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-blu"><i class="bx bx-buildings"></i></div><div class="mod-nm">Vendors</div><div class="mod-ds">Directory, payables and outstanding amounts.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-pri"><i class="bx bx-box"></i></div><div class="mod-nm">Products &amp; Stock</div><div class="mod-ds">Catalog, stock levels and reorder management.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-pur"><i class="bx bx-line-chart"></i></div><div class="mod-nm">Accounting</div><div class="mod-ds">Journals, reconciliation, P&amp;L, balance sheet.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-red"><i class="bx bx-money-withdraw"></i></div><div class="mod-nm">Expenses</div><div class="mod-ds">Record and categorise business expenses.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-grn"><i class="bx bx-trending-up"></i></div><div class="mod-nm">Indirect Income</div><div class="mod-ds">Track rent, interest and other non-sale income.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-amb"><i class="bx bx-group"></i></div><div class="mod-nm">HRMS</div><div class="mod-ds">Employees, roles, departments and access.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-blu"><i class="bx bx-bar-chart-alt-2"></i></div><div class="mod-nm">Reports</div><div class="mod-ds">Comprehensive reports — PDF, Excel, CSV export.</div></div>
    </div>
</section>

<!-- ── HOW IT WORKS ── -->
<section class="section" id="how">
    <div class="sec-center rv">
        <div class="sec-eye">Simple Onboarding</div>
        <h2 class="sec-title">Up and running in 3 steps</h2>
        <p class="sec-sub">No complicated setup. No IT team needed. Start managing your business today.</p>
    </div>
    <div class="proc-grid rv">
        <div class="proc-step">
            <div class="proc-n">01</div>
            <div class="proc-ico"><i class="bx bx-user-plus"></i></div>
            <div class="proc-ttl">Create your account</div>
            <div class="proc-p">Sign up for free in under 2 minutes. Add your business name, GST number, and you're ready. No credit card required.</div>
        </div>
        <div class="proc-step">
            <div class="proc-n">02</div>
            <div class="proc-ico"><i class="bx bx-customize"></i></div>
            <div class="proc-ttl">Configure your workspace</div>
            <div class="proc-p">Set up your products, customers, and vendors. Import existing data from Excel. Our onboarding guide walks you through each step.</div>
        </div>
        <div class="proc-step">
            <div class="proc-n">03</div>
            <div class="proc-ico"><i class="bx bx-rocket"></i></div>
            <div class="proc-ttl">Start transacting</div>
            <div class="proc-p">Create your first invoice, purchase order, or expense. Every transaction updates your inventory, accounts, and reports automatically.</div>
        </div>
    </div>
</section>

<!-- ── PRICING ── -->
<section class="section sec-alt" id="pricing">
    <div class="sec-center rv">
        <div class="sec-eye">Transparent Pricing</div>
        <h2 class="sec-title">Plans for every stage of growth</h2>
        <p class="sec-sub">Start free, scale as you grow. No hidden charges. Cancel anytime.</p>
    </div>

    <?php if ($_any && ($_hM || $_hY)): ?>
    <div class="pricing-tog rv">
        <span class="tog-lbl <?= $_hY ? '' : 'act' ?>" id="lblM">Monthly</span>
        <button class="tog-sw <?= $_hY ? 'on' : '' ?>" id="togBtn"><div class="tog-knob"></div></button>
        <span class="tog-lbl <?= $_hY ? 'act' : '' ?>" id="lblY">Yearly &nbsp;<span class="save-pill">Save <?= $_savePct ?>%</span></span>
    </div>
    <?php endif; ?>

    <?php if ($_any): ?>
    <div class="plans-grid" id="plansGrid">

        <?php if ($_hF): foreach ($_p['free'] as $plan):
            $mk = $plan->PlanUID . '_' . $plan->SectorUID;
            $mods = $_pm[$mk] ?? [];
        ?>
        <div class="plan-card rv d1">
            <div class="plan-cyc">Free</div>
            <div class="plan-nm"><?= htmlspecialchars($plan->PlanName) ?></div>
            <div class="plan-tag">Perfect for exploring the platform</div>
            <div class="plan-pr">
                <span class="plan-pr-cur"><?= $_cur ?></span>
                <span class="plan-pr-amt">0</span>
                <span class="plan-pr-per">/ forever</span>
            </div>
            <div class="plan-note">No credit card required</div>
            <?php if (!empty($plan->MaxUsers) || !empty($plan->MaxBranches)): ?>
            <div class="plan-lims">
                <?php if (!empty($plan->MaxUsers)): ?><span class="plan-lim"><i class="bx bx-user"></i><?= (int)$plan->MaxUsers ?> Users</span><?php endif; ?>
                <?php if (!empty($plan->MaxBranches)): ?><span class="plan-lim"><i class="bx bx-building"></i><?= (int)$plan->MaxBranches ?> Branch</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($mods)): ?>
            <div class="plan-div"></div>
            <ul class="plan-mods">
                <?php foreach (array_slice($mods, 0, 6) as $m): ?><li><i class="bx bx-check-circle"></i><?= htmlspecialchars($m->DisplayName) ?></li><?php endforeach; ?>
                <?php if (count($mods) > 6): ?><li class="more">+<?= count($mods)-6 ?> more included</li><?php endif; ?>
            </ul>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($_su) ?>" class="plan-btn p-btn-free">Get Started Free</a>
        </div>
        <?php endforeach; endif; ?>

        <?php foreach (['monthly','yearly'] as $cyc):
            if (empty($_p[$cyc])) continue;
            $cyc_plans = $_p[$cyc];
            $cnt = count($cyc_plans);
        ?>
        <div class="cyc-blk <?= ($_hY ? $cyc === 'yearly' : $cyc === 'monthly') ? 'vis' : '' ?>" id="blk_<?= $cyc ?>">
        <?php foreach ($cyc_plans as $idx => $plan):
            $isPop = ($idx === min($_popIdx, $cnt - 1));
            $mk    = $plan->PlanUID . '_' . $plan->SectorUID;
            $mods  = $_pm[$mk] ?? [];
            $price = (float)($plan->TotalAmount ?: $plan->Price ?: 0);
            $dur   = (int)($plan->DurationDays ?? 30);
            $per   = $cyc === 'yearly' ? '/ year' : '/ month';
        ?>
        <div class="plan-card <?= $isPop ? 'pop' : '' ?> rv d<?= min($idx+1,4) ?>">
            <div class="plan-cyc"><?= ucfirst($cyc) ?></div>
            <div class="plan-nm"><?= htmlspecialchars($plan->PlanName) ?></div>
            <div class="plan-tag"><?= $cyc === 'yearly' ? 'Best value — pay once, save more.' : 'Flexible month-to-month billing.' ?></div>
            <div class="plan-pr">
                <span class="plan-pr-cur"><?= $_cur ?></span>
                <span class="plan-pr-amt"><?= number_format($price, 0) ?></span>
                <span class="plan-pr-per"><?= $per ?></span>
            </div>
            <div class="plan-note">GST included &nbsp;·&nbsp; <?= $dur ?> days</div>
            <?php if (!empty($plan->MaxUsers) || !empty($plan->MaxBranches)): ?>
            <div class="plan-lims">
                <?php if (!empty($plan->MaxUsers)): ?><span class="plan-lim"><i class="bx bx-user"></i><?= (int)$plan->MaxUsers ?> Users</span><?php endif; ?>
                <?php if (!empty($plan->MaxBranches)): ?><span class="plan-lim"><i class="bx bx-building"></i><?= (int)$plan->MaxBranches ?> Branches</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($mods)): ?>
            <div class="plan-div"></div>
            <ul class="plan-mods">
                <?php foreach (array_slice($mods, 0, 7) as $m): ?><li><i class="bx bx-check-circle"></i><?= htmlspecialchars($m->DisplayName) ?></li><?php endforeach; ?>
                <?php if (count($mods) > 7): ?><li class="more">+<?= count($mods)-7 ?> more modules</li><?php endif; ?>
            </ul>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($_su) ?>" class="plan-btn <?= $isPop ? 'p-btn-solid' : 'p-btn-out' ?>">Get Started</a>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

    </div>
    <?php else: ?>
    <div class="no-plans rv"><i class="bx bx-tag"></i><p>Pricing plans coming soon. <a href="<?= htmlspecialchars($_su) ?>" class="link-pri">Contact us</a> for a quote.</p></div>
    <?php endif; ?>
</section>

<!-- ── TESTIMONIALS ── -->
<section class="section">
    <div class="sec-center rv">
        <div class="sec-eye">What Our Customers Say</div>
        <h2 class="sec-title">Trusted by businesses like yours</h2>
    </div>
    <div class="testi-grid">
        <div class="testi-card rv d1">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">R2K ERP transformed how we manage invoicing and inventory. Everything is in one place and the GST reports save us hours every month.</div>
            <div class="testi-author">
                <div class="testi-av av-a">RK</div>
                <div><div class="testi-nm">Rajan Krishnan</div><div class="testi-role">Managing Director, Sun Traders</div></div>
            </div>
        </div>
        <div class="testi-card rv d2">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">The accounting module alone replaced our external software. Our team got comfortable with it in less than a week — very intuitive.</div>
            <div class="testi-author">
                <div class="testi-av av-b">PS</div>
                <div><div class="testi-nm">Priya Shankar</div><div class="testi-role">Accounts Manager, Mahalakshmi Stores</div></div>
            </div>
        </div>
        <div class="testi-card rv d3">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">We run 3 branches and the multi-branch stock visibility is exceptional. We can see everything from one dashboard in real time.</div>
            <div class="testi-author">
                <div class="testi-av av-c">AM</div>
                <div><div class="testi-nm">Arjun Mehta</div><div class="testi-role">CEO, Nova Enterprises</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA BAND ── -->
<div class="cta-band rv">
    <div class="cta-orb1"></div>
    <div class="cta-orb2"></div>
    <div class="cta-ttl">Your business deserves better tools.</div>
    <div class="cta-sub">Start your free trial today. No credit card. No commitment.</div>
    <div class="cta-btns">
        <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri btn-lg"><i class="bx bx-rocket"></i> Start Free Trial</a>
        <a href="<?= htmlspecialchars($_lu) ?>" class="btn btn-out btn-lg">Sign In</a>
    </div>
</div>

<!-- ── FOOTER ── -->
<footer class="footer">
    <div class="foot-top">
        <div>
            <div class="nav-logo foot-logo">
                <div class="nav-mark"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K ERP"></div>
                <span>R2K ERP</span>
            </div>
            <div class="foot-brand-copy">Complete business management software built for growing Indian businesses. GST compliant. Cloud hosted. Always on.</div>
        </div>
        <div>
            <div class="foot-col-ttl">Product</div>
            <ul class="foot-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#modules">Modules</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#how">How it works</a></li>
            </ul>
        </div>
        <div>
            <div class="foot-col-ttl">Quick Access</div>
            <ul class="foot-links">
                <li><a href="<?= htmlspecialchars($_su) ?>">Sign Up</a></li>
                <li><a href="<?= htmlspecialchars($_lu) ?>">Sign In</a></li>
                <li><a href="<?= htmlspecialchars($_su) ?>">Free Trial</a></li>
            </ul>
        </div>
        <div>
            <div class="foot-col-ttl">Compliance</div>
            <ul class="foot-links">
                <li><a href="#">GST Compliant</a></li>
                <li><a href="#">Data Security</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
    </div>
    <div class="foot-btm">
        <div class="foot-copy">&copy; <?= date('Y') ?> R2K Enterprises. All rights reserved.</div>
        <div class="foot-badges">
            <span class="foot-bdg"><i class="bx bx-shield-check"></i> GST Compliant</span>
            <span class="foot-bdg"><i class="bx bx-lock-alt"></i> SSL Secured</span>
            <span class="foot-bdg"><i class="bx bx-cloud"></i> 99.9% Uptime</span>
        </div>
    </div>
</footer>

<script>
$(function () {

    /* ── Navbar scroll state ── */
    /**
     * Toggles the scrolled class on the navbar based on scroll position.
     * @returns {void}
     */
    function checkNavScroll() {
        if ($(window).scrollTop() > 40) {
            $('#mainNav').addClass('scrolled');
        } else {
            $('#mainNav').removeClass('scrolled');
        }
    }
    $(window).on('scroll', checkNavScroll);
    checkNavScroll();

    /* ── Scroll reveal ── */
    /**
     * Marks reveal elements as visible when they enter the viewport.
     * @returns {void}
     */
    function checkReveal() {
        var vBot = $(window).scrollTop() + $(window).height();
        $('.rv, .rv-l, .rv-r, .rv-s').each(function () {
            if ($(this).offset().top < vBot - 60) {
                $(this).addClass('vis');
            }
        });
    }
    $(window).on('scroll', checkReveal);
    checkReveal();

    /* ── Counter animation ── */
    var _counted = false;
    /**
     * Animates .counter elements from 0 to their data-target value.
     * @returns {void}
     */
    function runCounters() {
        if (_counted) { return; }
        var statsTop = $('.stats-band').offset().top;
        if ($(window).scrollTop() + $(window).height() > statsTop) {
            _counted = true;
            $('.counter').each(function () {
                var $el    = $(this);
                var target = parseInt($el.data('target'), 10);
                $({ n: 0 }).animate({ n: target }, {
                    duration: 1600,
                    easing: 'swing',
                    step: function () { $el.text(Math.floor(this.n)); },
                    complete: function () { $el.text(target); }
                });
            });
        }
    }
    $(window).on('scroll', runCounters);
    runCounters();

    /* ── Pricing toggle ── */
    var _cyc = <?= $_hY ? "'yearly'" : "'monthly'" ?>;
    /**
     * Switches the visible pricing cycle between monthly and yearly.
     * @returns {void}
     */
    function toggleCycle() {
        _cyc = (_cyc === 'monthly') ? 'yearly' : 'monthly';
        var isYearly = (_cyc === 'yearly');
        $('#togBtn').toggleClass('on', isYearly);
        $('#lblY').toggleClass('act', isYearly);
        $('#lblM').toggleClass('act', !isYearly);
        $('#blk_monthly').toggleClass('vis', !isYearly);
        $('#blk_yearly').toggleClass('vis', isYearly);
    }
    $('#togBtn').on('click', toggleCycle);

});
</script>
</body>
</html>
