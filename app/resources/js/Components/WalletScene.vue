<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';

const containerRef = ref(null);
let scene, camera, renderer, animationId;
let piggyGroup;
let coins = [];
let time = 0;
let envMap = null;

// ─── Environment map ───
function createEnvMap(r) {
    const pmrem = new THREE.PMREMGenerator(r);
    const s = new THREE.Scene();
    const l1 = new THREE.DirectionalLight(0xfff5e6, 3);
    l1.position.set(3, 5, 4);
    s.add(l1);
    const l2 = new THREE.DirectionalLight(0xe0e8ff, 1.5);
    l2.position.set(-4, 3, 2);
    s.add(l2);
    const l3 = new THREE.DirectionalLight(0xffeedd, 1);
    l3.position.set(0, -2, 5);
    s.add(l3);
    const geo = new THREE.PlaneGeometry(20, 20);
    [
        { pos: [0, 8, -6], color: 0xffffff },
        { pos: [8, 4, 0], color: 0xfff8ee, ry: -Math.PI / 2 },
        { pos: [-8, 4, 0], color: 0xeef0ff, ry: Math.PI / 2 },
        { pos: [0, -5, 0], color: 0xf8f4ee, rx: -Math.PI / 2 },
    ].forEach(p => {
        const m = new THREE.Mesh(geo, new THREE.MeshBasicMaterial({ color: p.color, side: THREE.DoubleSide }));
        m.position.set(...p.pos);
        if (p.rx) m.rotation.x = p.rx;
        if (p.ry) m.rotation.y = p.ry;
        s.add(m);
    });
    s.background = new THREE.Color(0x334455);
    const tex = pmrem.fromScene(s, 0.02).texture;
    pmrem.dispose();
    return tex;
}

// ─── Piggy bank ───
function createPiggyBank() {
    const group = new THREE.Group();

    const piggyPink = new THREE.MeshStandardMaterial({
        color: 0xf4a0b0,
        roughness: 0.35,
        metalness: 0.05,
        envMap,
        envMapIntensity: 0.4,
    });
    const piggyDark = new THREE.MeshStandardMaterial({
        color: 0xe0849a,
        roughness: 0.4,
        metalness: 0.05,
        envMap,
        envMapIntensity: 0.3,
    });
    const piggyLight = new THREE.MeshStandardMaterial({
        color: 0xfbc0cc,
        roughness: 0.3,
        metalness: 0.05,
        envMap,
        envMapIntensity: 0.35,
    });
    const nosePink = new THREE.MeshStandardMaterial({
        color: 0xe87090,
        roughness: 0.45,
        metalness: 0.0,
    });
    const eyeWhite = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        roughness: 0.2,
        metalness: 0.0,
    });
    const eyeBlack = new THREE.MeshStandardMaterial({
        color: 0x1a1a2e,
        roughness: 0.15,
        metalness: 0.1,
    });
    const eyeShine = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        roughness: 0.0,
        metalness: 0.0,
        emissive: 0xffffff,
        emissiveIntensity: 0.3,
    });

    // ─── Body (stretched sphere) ───
    const bodyGeo = new THREE.SphereGeometry(1, 48, 36);
    const body = new THREE.Mesh(bodyGeo, piggyPink);
    body.scale.set(1.25, 1.0, 1.0);
    group.add(body);

    // ─── Belly highlight ───
    const bellyGeo = new THREE.SphereGeometry(0.75, 32, 24);
    const belly = new THREE.Mesh(bellyGeo, piggyLight);
    belly.scale.set(1.1, 0.8, 0.7);
    belly.position.set(0, -0.25, 0.45);
    group.add(belly);

    // ─── Snout ───
    const snoutGeo = new THREE.CylinderGeometry(0.38, 0.42, 0.35, 32);
    const snout = new THREE.Mesh(snoutGeo, piggyDark);
    snout.rotation.x = Math.PI / 2;
    snout.position.set(0, -0.05, 1.22);
    group.add(snout);

    // Snout flat face
    const snoutFaceGeo = new THREE.CircleGeometry(0.38, 32);
    const snoutFace = new THREE.Mesh(snoutFaceGeo, nosePink);
    snoutFace.position.set(0, -0.05, 1.40);
    group.add(snoutFace);

    // Nostrils
    for (let side = -1; side <= 1; side += 2) {
        const nostrilGeo = new THREE.SphereGeometry(0.06, 16, 12);
        const nostril = new THREE.Mesh(nostrilGeo, new THREE.MeshStandardMaterial({
            color: 0xc44060, roughness: 0.6, metalness: 0.0,
        }));
        nostril.scale.set(1.3, 0.8, 0.5);
        nostril.position.set(side * 0.13, -0.08, 1.42);
        group.add(nostril);
    }

    // ─── Eyes ───
    for (let side = -1; side <= 1; side += 2) {
        const whiteGeo = new THREE.SphereGeometry(0.14, 24, 18);
        const white = new THREE.Mesh(whiteGeo, eyeWhite);
        white.position.set(side * 0.38, 0.28, 0.88);
        group.add(white);

        const irisGeo = new THREE.SphereGeometry(0.085, 20, 16);
        const iris = new THREE.Mesh(irisGeo, eyeBlack);
        iris.position.set(side * 0.36, 0.28, 1.0);
        group.add(iris);

        const shineGeo = new THREE.SphereGeometry(0.035, 12, 10);
        const shine = new THREE.Mesh(shineGeo, eyeShine);
        shine.position.set(side * 0.33, 0.32, 1.04);
        group.add(shine);
    }

    // ─── Ears ───
    for (let side = -1; side <= 1; side += 2) {
        const earGeo = new THREE.ConeGeometry(0.22, 0.38, 16);
        const ear = new THREE.Mesh(earGeo, piggyDark);
        ear.position.set(side * 0.5, 0.9, 0.25);
        ear.rotation.z = side * 0.4;
        ear.rotation.x = -0.2;
        group.add(ear);

        const innerEarGeo = new THREE.ConeGeometry(0.13, 0.25, 12);
        const innerEar = new THREE.Mesh(innerEarGeo, nosePink);
        innerEar.position.set(side * 0.5, 0.88, 0.30);
        innerEar.rotation.z = side * 0.4;
        innerEar.rotation.x = -0.2;
        group.add(innerEar);
    }

    // ─── Legs (4 stubby cylinders) ───
    const legPositions = [
        [-0.55, -0.85, 0.4],
        [0.55, -0.85, 0.4],
        [-0.55, -0.85, -0.4],
        [0.55, -0.85, -0.4],
    ];
    legPositions.forEach(pos => {
        const legGeo = new THREE.CylinderGeometry(0.18, 0.2, 0.35, 16);
        const leg = new THREE.Mesh(legGeo, piggyDark);
        leg.position.set(...pos);
        group.add(leg);

        const hoofGeo = new THREE.CylinderGeometry(0.21, 0.21, 0.06, 16);
        const hoof = new THREE.Mesh(hoofGeo, nosePink);
        hoof.position.set(pos[0], pos[1] - 0.19, pos[2]);
        group.add(hoof);
    });

    // ─── Tail (curly) ───
    const tailPts = [];
    for (let t = 0; t < Math.PI * 3; t += 0.15) {
        const r = 0.08 + t * 0.02;
        tailPts.push(new THREE.Vector3(
            -1.25 - t * 0.06,
            0.1 + Math.sin(t) * r,
            Math.cos(t) * r
        ));
    }
    const tailCurve = new THREE.CatmullRomCurve3(tailPts);
    const tailGeo = new THREE.TubeGeometry(tailCurve, 40, 0.04, 8, false);
    const tail = new THREE.Mesh(tailGeo, piggyDark);
    group.add(tail);

    // ─── Coin slot on top ───
    const slotW = 0.45, slotH = 0.06, slotD = 0.12;
    const slotGeo = new THREE.BoxGeometry(slotW, slotD, slotH);
    const slotMat = new THREE.MeshStandardMaterial({
        color: 0x2a1a1a,
        roughness: 0.8,
        metalness: 0.0,
    });
    const slot = new THREE.Mesh(slotGeo, slotMat);
    slot.position.set(0, 1.0, 0);
    group.add(slot);

    const slotRimGeo = new THREE.BoxGeometry(slotW + 0.06, slotD + 0.02, slotH + 0.06);
    const slotRimMat = new THREE.MeshStandardMaterial({
        color: 0xe0849a,
        roughness: 0.4,
        metalness: 0.05,
    });
    const slotRim = new THREE.Mesh(slotRimGeo, slotRimMat);
    slotRim.position.set(0, 0.99, 0);
    group.add(slotRim);

    // ─── Cheek blush ───
    for (let side = -1; side <= 1; side += 2) {
        const blushGeo = new THREE.CircleGeometry(0.15, 24);
        const blushMat = new THREE.MeshStandardMaterial({
            color: 0xff7088,
            transparent: true,
            opacity: 0.35,
            roughness: 0.5,
            metalness: 0.0,
            side: THREE.DoubleSide,
        });
        const blush = new THREE.Mesh(blushGeo, blushMat);
        blush.position.set(side * 0.6, 0.0, 0.92);
        blush.lookAt(side * 0.6 + side * 0.2, 0.0, 2);
        group.add(blush);
    }

    // ─── Smile ───
    const smilePts = [];
    for (let t = -0.6; t <= 0.6; t += 0.05) {
        smilePts.push(new THREE.Vector3(
            Math.sin(t) * 0.22,
            -0.28 - Math.cos(t) * 0.06,
            1.18 + Math.cos(t) * 0.04
        ));
    }
    const smileCurve = new THREE.CatmullRomCurve3(smilePts);
    const smileGeo = new THREE.TubeGeometry(smileCurve, 20, 0.015, 6, false);
    const smileMat = new THREE.MeshStandardMaterial({
        color: 0xc44060,
        roughness: 0.5,
        metalness: 0.0,
    });
    const smile = new THREE.Mesh(smileGeo, smileMat);
    group.add(smile);

    // ─── Dollar sign on body ───
    const dollarMat = new THREE.MeshStandardMaterial({
        color: 0xd4af37,
        roughness: 0.2,
        metalness: 0.8,
        envMap,
        envMapIntensity: 1.0,
    });

    // S-curve of the dollar sign
    const sPts = [];
    // Top curve (right-facing arc)
    for (let t = 0; t <= Math.PI; t += 0.12) {
        sPts.push(new THREE.Vector3(
            -0.55 + Math.cos(t) * 0.10,
            0.18 + Math.sin(t) * 0.09,
            0
        ));
    }
    // Bottom curve (left-facing arc)
    for (let t = Math.PI; t <= Math.PI * 2; t += 0.12) {
        sPts.push(new THREE.Vector3(
            -0.55 + Math.cos(t) * 0.10,
            0.0 + Math.sin(t) * 0.09,
            0
        ));
    }
    const sCurve = new THREE.CatmullRomCurve3(sPts);
    const sGeo = new THREE.TubeGeometry(sCurve, 30, 0.018, 8, false);
    const sMesh = new THREE.Mesh(sGeo, dollarMat);

    // Vertical bar through the S
    const barGeo = new THREE.CylinderGeometry(0.015, 0.015, 0.46, 8);
    const bar = new THREE.Mesh(barGeo, dollarMat);
    bar.position.set(-0.55, 0.09, 0);
    sMesh.add(bar);

    // Position dollar sign on the right side of the body
    sMesh.position.set(0, 0, 0);
    sMesh.rotation.y = 0;

    // Create a group to rotate dollar onto body surface
    const dollarGroup = new THREE.Group();
    dollarGroup.add(sMesh);
    dollarGroup.rotation.y = -0.55;
    dollarGroup.position.set(0, 0, 0);

    // Project onto the sphere surface
    const dollarWrapper = new THREE.Group();
    dollarWrapper.add(dollarGroup);
    dollarWrapper.position.set(0.7, 0.05, 0.7);
    dollarWrapper.rotation.y = -0.75;
    group.add(dollarWrapper);

    // ─── Position ───
    group.rotation.set(-0.1, -0.4, 0.0);
    group.position.set(0, -0.2, 0);

    return group;
}

// ─── Coin slot position in world space ───
function getSlotWorldPos() {
    if (!piggyGroup) return new THREE.Vector3(0, 2, 0);
    piggyGroup.updateMatrixWorld(true);
    return new THREE.Vector3(0, 1.0, 0).applyMatrix4(piggyGroup.matrixWorld);
}

// ─── Coin creation ───
function createCoin(isGold) {
    const radius = 0.10 + Math.random() * 0.03;
    const thickness = 0.02;
    const geo = new THREE.CylinderGeometry(radius, radius, thickness, 32);

    const color = isGold
        ? new THREE.Color().setHSL(0.11 + Math.random() * 0.03, 0.9, 0.52)
        : new THREE.Color().setHSL(0.0, 0.0, 0.72 + Math.random() * 0.12);

    const mat = new THREE.MeshStandardMaterial({
        color,
        metalness: 1.0,
        roughness: isGold ? 0.16 : 0.10,
        envMap,
        envMapIntensity: 1.6,
    });

    const coin = new THREE.Mesh(geo, mat);

    const rimGeo = new THREE.TorusGeometry(radius - 0.003, 0.005, 6, 32);
    const rimMat = new THREE.MeshStandardMaterial({
        color: color.clone().multiplyScalar(0.75),
        metalness: 1.0,
        roughness: isGold ? 0.2 : 0.12,
        envMap,
        envMapIntensity: 1.0,
    });
    const rimT = new THREE.Mesh(rimGeo, rimMat);
    rimT.position.y = thickness / 2;
    rimT.rotation.x = Math.PI / 2;
    coin.add(rimT);
    const rimB = rimT.clone();
    rimB.position.y = -thickness / 2;
    coin.add(rimB);

    const faceGeo = new THREE.CircleGeometry(radius * 0.45, 20);
    const faceMat = new THREE.MeshStandardMaterial({
        color: color.clone().multiplyScalar(0.88),
        metalness: 1.0,
        roughness: isGold ? 0.22 : 0.15,
        envMap,
        envMapIntensity: 1.2,
        side: THREE.DoubleSide,
    });
    const face = new THREE.Mesh(faceGeo, faceMat);
    face.rotation.x = -Math.PI / 2;
    face.position.y = thickness / 2 + 0.001;
    coin.add(face);

    return coin;
}

function spawnCoin() {
    const isGold = Math.random() > 0.3;
    const coin = createCoin(isGold);

    const slotPos = getSlotWorldPos();

    coin.position.set(
        slotPos.x + (Math.random() - 0.5) * 0.15,
        slotPos.y + 1.8 + Math.random() * 1.2,
        slotPos.z + (Math.random() - 0.5) * 0.1,
    );

    // Orient coin vertically to fit through slot
    coin.rotation.set(
        0,
        Math.random() * Math.PI * 2,
        Math.PI / 2 + (Math.random() - 0.5) * 0.3,
    );

    coin.userData = {
        vy: -0.006 - Math.random() * 0.008,
        spinY: (Math.random() - 0.5) * 0.08,
        wobble: Math.random() * Math.PI * 2,
        phase: 0,
        landTime: 0,
    };

    scene.add(coin);
    coins.push(coin);
}

function animate() {
    animationId = requestAnimationFrame(animate);
    time += 0.016;

    if (piggyGroup) {
        piggyGroup.position.y = -0.2 + Math.sin(time * 0.5) * 0.04;
        piggyGroup.rotation.y = -0.4 + Math.sin(time * 0.22) * 0.06;
        piggyGroup.rotation.x = -0.1 + Math.sin(time * 0.3) * 0.015;
    }

    if (Math.random() < 0.022) spawnCoin();

    const slotPos = getSlotWorldPos();

    for (let i = coins.length - 1; i >= 0; i--) {
        const c = coins[i];
        const d = c.userData;

        if (d.phase === 0) {
            d.vy -= 0.0004;
            c.position.y += d.vy;
            c.rotation.y += d.spinY;
            c.position.x += Math.sin(d.wobble + time * 1.5) * 0.0006;

            if (c.position.y <= slotPos.y + 0.15) {
                d.phase = 1;
                d.landTime = time;
                d.spinY *= 0.3;
                d.vy *= 0.2;
            }
        } else {
            d.vy -= 0.00005;
            c.position.y += d.vy;
            c.rotation.y += d.spinY;

            c.position.x += (slotPos.x - c.position.x) * 0.1;
            c.position.z += (slotPos.z - c.position.z) * 0.1;

            const elapsed = time - d.landTime;
            const fadeProgress = Math.min(elapsed / 0.4, 1.0);
            const s = Math.max(0, 1.0 - fadeProgress);
            c.scale.setScalar(s);

            c.traverse(child => {
                if (child.isMesh) {
                    child.material.transparent = true;
                    child.material.opacity = s;
                }
            });

            if (s <= 0) {
                scene.remove(c);
                c.traverse(child => {
                    if (child.isMesh) { child.geometry.dispose(); child.material.dispose(); }
                });
                coins.splice(i, 1);
            }
        }
    }

    renderer.render(scene, camera);
}

function onResize() {
    if (!containerRef.value || !renderer) return;
    const w = containerRef.value.clientWidth;
    const h = containerRef.value.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
}

onMounted(() => {
    const container = containerRef.value;
    if (!container) return;

    scene = new THREE.Scene();

    camera = new THREE.PerspectiveCamera(30, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 1.5, 6.5);
    camera.lookAt(0, 0.1, 0);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    envMap = createEnvMap(renderer);

    scene.add(new THREE.AmbientLight(0xfff0f5, 0.6));

    const keyLight = new THREE.DirectionalLight(0xfff5e6, 2.0);
    keyLight.position.set(3, 6, 5);
    scene.add(keyLight);

    const fillLight = new THREE.DirectionalLight(0xd0e0ff, 0.6);
    fillLight.position.set(-4, 3, 3);
    scene.add(fillLight);

    const rimLight = new THREE.DirectionalLight(0xffeedd, 0.5);
    rimLight.position.set(0, 2, -5);
    scene.add(rimLight);

    const bottomFill = new THREE.DirectionalLight(0xfff0dd, 0.3);
    bottomFill.position.set(0, -3, 2);
    scene.add(bottomFill);

    const coinSpot = new THREE.PointLight(0xffe4a0, 1.5, 12);
    coinSpot.position.set(0, 5, 1);
    scene.add(coinSpot);

    piggyGroup = createPiggyBank();
    scene.add(piggyGroup);

    for (let i = 0; i < 4; i++) {
        setTimeout(() => spawnCoin(), i * 300);
    }

    animate();
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(animationId);
    window.removeEventListener('resize', onResize);
    coins.forEach(c => {
        c.traverse(child => {
            if (child.isMesh) { child.geometry.dispose(); child.material.dispose(); }
        });
    });
    coins = [];
    if (renderer) renderer.dispose();
    if (envMap) envMap.dispose();
});
</script>

<template>
    <div ref="containerRef" class="w-full h-full" />
</template>