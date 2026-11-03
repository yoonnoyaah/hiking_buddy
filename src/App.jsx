import React, { useEffect, useState } from 'react';
import {
  Routes, Route, Link, NavLink, Navigate, useNavigate, useParams
} from 'react-router-dom';
import {
  Mountain, ArrowRight, ArrowUpRight, Menu, X, MapPin,
  CalendarDays, Clock3, Users, Gauge, Search, Copy, Check,
  Plus, LogOut, Settings, BadgeCheck, Compass, Send,
  Footprints, TentTree, Heart, Mail, LockKeyhole,
  LayoutDashboard, Trash2, SlidersHorizontal
} from 'lucide-react';
import { trails, initialTrips } from './data';

// --------------------------------------------------
// STORAGE HELPERS
// --------------------------------------------------

const stored = (key, fallback) => {
  try {
    return JSON.parse(localStorage.getItem(key)) ?? fallback;
  } catch {
    return fallback;
  }
};

const save = (key, value) => {
  localStorage.setItem(key, JSON.stringify(value));
};

// --------------------------------------------------
// LARAVEL API CONFIGURATION
// --------------------------------------------------

const API_BASE = 'http://127.0.0.1:8000/api';

const getAuthToken = () => {
  return localStorage.getItem('hb-token');
};

const mapApiUser = (apiUser) => ({
  id: apiUser.id,
  name: apiUser.name,
  email: apiUser.email,
  city: apiUser.city,
  fitness: apiUser.fitness_level || 'Intermediate',
  pace: apiUser.preferred_pace || 'Steady',
  role: apiUser.role,
  status: apiUser.status
});

const regions = ['All regions', 'Yangon', 'Mandalay', 'Bagan'];
const levels = ['All levels', 'Easy', 'Moderate', 'Hard'];

const imgFallback =
  'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=85';

// --------------------------------------------------
// HEADER
// --------------------------------------------------

function Header({ user, onLogout }) {
  const [open, setOpen] = useState(false);

  return (
    <header className="sticky top-0 z-40 border-b border-stone-200/80 bg-white/95 backdrop-blur">
      <div className="container-shell flex h-[76px] items-center justify-between">

        <Link
          to="/"
          className="flex items-center gap-2.5"
          onClick={() => setOpen(false)}
        >
          <span className="grid h-10 w-10 place-items-center rounded-full bg-sage-600 text-white">
            <Mountain size={22} />
          </span>

          <span className="font-bold tracking-tight text-ink">
            hiking<span className="text-sage-600">buddy</span>
            <span className="ml-1 text-xs font-normal text-stone-400">®</span>
          </span>
        </Link>

        <nav
          className={`${
            open
              ? 'absolute left-0 right-0 top-[76px] flex border-b border-stone-200 bg-white p-5 shadow-soft'
              : 'hidden'
          } flex-col gap-5 md:static md:flex md:flex-row md:items-center md:border-0 md:bg-transparent md:p-0 md:shadow-none`}
        >
          <NavLink onClick={() => setOpen(false)} to="/" className={({ isActive }) => `nav-link ${isActive ? 'nav-active' : ''}`}>
            Home
          </NavLink>

          <NavLink onClick={() => setOpen(false)} to="/trails" className={({ isActive }) => `nav-link ${isActive ? 'nav-active' : ''}`}>
            Trails
          </NavLink>

          <NavLink onClick={() => setOpen(false)} to="/community" className={({ isActive }) => `nav-link ${isActive ? 'nav-active' : ''}`}>
            Community
          </NavLink>

          {user && (
            <NavLink onClick={() => setOpen(false)} to="/profile" className={({ isActive }) => `nav-link ${isActive ? 'nav-active' : ''}`}>
              Profile
            </NavLink>
          )}
        </nav>

        <div className="flex items-center gap-3">
          {user ? (
            <div className="hidden items-center gap-2 sm:flex">
              <Link
                to="/profile"
                className="flex items-center gap-2 rounded-full bg-sage-50 py-1.5 pl-1.5 pr-3 text-sm font-semibold text-sage-700"
              >
                <span className="avatar">
                  {user.name?.[0]?.toUpperCase() || 'H'}
                </span>
                {user.name?.split(' ')[0] || 'My profile'}
              </Link>

              <button
                aria-label="Log out"
                onClick={onLogout}
                className="icon-button"
              >
                <LogOut size={17} />
              </button>
            </div>
          ) : (
            <Link to="/login" className="btn-primary !rounded-full !px-5 !py-2.5">
              Login <ArrowUpRight size={16} />
            </Link>
          )}

          <button
            onClick={() => setOpen(!open)}
            className="icon-button md:hidden"
            aria-label="Toggle menu"
          >
            {open ? <X /> : <Menu />}
          </button>
        </div>
      </div>
    </header>
  );
}

// --------------------------------------------------
// FOOTER
// --------------------------------------------------

function Footer() {
  return (
    <footer className="mt-20 bg-[#25291f] text-white">
      <div className="container-shell grid gap-10 py-12 md:grid-cols-[1.5fr_1fr_1fr]">

        <div>
          <Link to="/" className="flex items-center gap-2 font-bold text-lg">
            <Mountain /> hikingbuddy
          </Link>

          <p className="mt-4 max-w-sm text-sm leading-6 text-stone-300">
            Find your trail. Meet your people. Make every outdoor day count.
          </p>
        </div>

        <div>
          <p className="font-semibold">Explore</p>

          <div className="mt-4 flex flex-col gap-3 text-sm text-stone-300">
            <Link to="/trails" className="hover:text-white">Discover trails</Link>
            <Link to="/trails?create=true" className="hover:text-white">Organise a trip</Link>
            <Link to="/profile" className="hover:text-white">Your profile</Link>
          </div>
        </div>

        <div>
          <p className="font-semibold">Community</p>

          <div className="mt-4 flex flex-col gap-3 text-sm text-stone-300">
            <a href="#safety" className="hover:text-white">Hiking safety</a>
            <a href="#privacy" className="hover:text-white">Privacy policy</a>
            <a href="#cookies" className="hover:text-white">Cookie preferences</a>
          </div>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="container-shell flex flex-col justify-between gap-2 py-5 text-xs text-stone-400 sm:flex-row">
          <span>© 2026 Hiking Buddy Community. Made for the outdoors.</span>
          <span>Explore responsibly · Leave no trace</span>
        </div>
      </div>
    </footer>
  );
}

// --------------------------------------------------
// COOKIE BANNER
// --------------------------------------------------

function CookieBanner() {
  const [show, setShow] = useState(
    () => !localStorage.getItem('hb-cookie-choice')
  );

  const choose = value => {
    localStorage.setItem('hb-cookie-choice', value);
    setShow(false);
  };

  if (!show) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 z-50 mx-auto flex max-w-4xl flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-2xl sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p className="font-semibold text-ink">A little trail housekeeping 🍃</p>
        <p className="mt-1 text-sm text-stone-500">
          We use essential local storage to remember preferences and demo sign-in.
        </p>
      </div>

      <div className="flex shrink-0 gap-2">
        <button className="btn-secondary" onClick={() => choose('declined')}>
          Decline
        </button>

        <button className="btn-primary" onClick={() => choose('accepted')}>
          Accept
        </button>
      </div>
    </div>
  );
}

// --------------------------------------------------
// REPORT BUTTON
// --------------------------------------------------

function ReportButton({ type = 'post', id, title, authorEmail, currentUserEmail }) {
  const [sent, setSent] = useState(false);

  if (authorEmail && currentUserEmail && authorEmail === currentUserEmail) {
    return null;
  }

  const report = () => {
    const reason = window.prompt(`Why are you reporting this ${type}?`);

    if (!reason?.trim()) return;

    const reports = stored('hb-reports', []);

    reports.unshift({
      id: Date.now(),
      type,
      contentId: id,
      title,
      reason: reason.trim(),
      status: 'Pending',
      createdAt: new Date().toISOString()
    });

    save('hb-reports', reports);
    setSent(true);
  };

  return (
    <button
      type="button"
      onClick={report}
      className="btn-secondary !px-3 !py-2 text-xs"
    >
      {sent ? 'Reported ✓' : 'Report'}
    </button>
  );
}

// --------------------------------------------------
// COMMUNITY PAGE
// Users can create, edit and delete their own posts.
// --------------------------------------------------

function Community({ user }) {
  const [posts, setPosts] = useState(
    () => stored('hb-community-posts', [])
  );

  const [body, setBody] = useState('');
  const [photo, setPhoto] = useState('');
  const [topic, setTopic] = useState('Trail experience');
  const [commentDrafts, setCommentDrafts] = useState({});

  const [editingId, setEditingId] = useState(null);
  const [editBody, setEditBody] = useState('');
  const [editTopic, setEditTopic] = useState('');

  const persist = next => {
    setPosts(next);
    save('hb-community-posts', next);
  };

  const publish = e => {
    e.preventDefault();

    if (!user) {
      window.location.href = '/login?next=/community';
      return;
    }

    if (!body.trim() && !photo) return;

    const next = [
      {
        id: Date.now(),
        author: user.name,
        email: user.email,
        topic,
        body: body.trim(),
        photo,
        likes: [],
        comments: [],
        date: new Date().toISOString()
      },
      ...posts
    ];

    persist(next);
    setBody('');
    setPhoto('');
  };

  const addPhoto = e => {
    const file = e.target.files?.[0];

    if (!file) return;

    if (!file.type.startsWith('image/')) {
      alert('Please choose an image file.');
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      alert('Please choose an image smaller than 2 MB.');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => setPhoto(reader.result);
    reader.readAsDataURL(file);
  };

  const startEdit = post => {
    setEditingId(post.id);
    setEditBody(post.body || '');
    setEditTopic(post.topic || 'Trail experience');
  };

  const saveEdit = id => {
    if (!editBody.trim()) {
      alert('Post content cannot be empty.');
      return;
    }

    const updated = posts.map(post =>
      post.id === id && post.email === user?.email
        ? { ...post, body: editBody.trim(), topic: editTopic }
        : post
    );

    persist(updated);
    setEditingId(null);
  };

  const deletePost = id => {
    if (!window.confirm('Are you sure you want to delete this post?')) return;

    persist(
      posts.filter(post =>
        !(post.id === id && post.email === user?.email)
      )
    );
  };

  const toggleLike = id => {
    if (!user) {
      window.location.href = '/login?next=/community';
      return;
    }

    persist(
      posts.map(post =>
        post.id === id
          ? {
              ...post,
              likes: (post.likes || []).includes(user.email)
                ? post.likes.filter(email => email !== user.email)
                : [...(post.likes || []), user.email]
            }
          : post
      )
    );
  };

  const addComment = (e, post) => {
    e.preventDefault();

    if (!user) {
      window.location.href = '/login?next=/community';
      return;
    }

    const text = (commentDrafts[post.id] || '').trim();
    if (!text) return;

    persist(
      posts.map(item =>
        item.id === post.id
          ? {
              ...item,
              comments: [
                ...(item.comments || []),
                {
                  id: Date.now(),
                  author: user.name,
                  email: user.email,
                  body: text,
                  date: new Date().toISOString()
                }
              ]
            }
          : item
      )
    );

    setCommentDrafts({
      ...commentDrafts,
      [post.id]: ''
    });
  };

  return (
    <main className="container-shell page-pad">
      <p className="eyebrow">HIKERS, STORIES AND SHARED MOMENTS</p>

      <h1 className="page-title">
        The hiking <span className="text-sage-600">community.</span>
      </h1>

      <p className="mt-3 max-w-2xl leading-7 text-stone-500">
        Share hiking photos and experiences, like posts, and join the conversation.
      </p>

      <form
        onSubmit={publish}
        className="mt-7 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm"
      >
        <div className="flex items-center gap-3">
          <div className="avatar">
            {user?.name?.[0]?.toUpperCase() || 'H'}
          </div>

          <p className="font-semibold text-ink">
            {user ? `Share something, ${user.name.split(' ')[0]}` : 'Log in to share with the community'}
          </p>
        </div>

        <label className="field-label mt-4">
          Post category

          <select
            className="field mt-2"
            value={topic}
            onChange={e => setTopic(e.target.value)}
          >
            <option>Trail experience</option>
            <option>Hiking tips</option>
            <option>Questions for hikers</option>
            <option>Photos and memories</option>
          </select>
        </label>

        <textarea
          className="field mt-4 min-h-28"
          value={body}
          onChange={e => setBody(e.target.value)}
          placeholder="What is happening on your hiking journey?"
        />

        {photo && (
          <div className="relative mb-3 mt-3 inline-block">
            <img
              src={photo}
              alt="Selected hiking upload"
              className="max-h-64 rounded-xl object-cover"
            />

            <button
              type="button"
              onClick={() => setPhoto('')}
              className="absolute right-2 top-2 rounded-full bg-black/70 px-2 py-1 text-xs text-white"
            >
              Remove
            </button>
          </div>
        )}

        <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
          <label className="btn-secondary cursor-pointer !px-3 !py-2 text-xs">
            ＋ Add photo
            <input
              type="file"
              accept="image/*"
              className="hidden"
              onChange={addPhoto}
            />
          </label>

          <button className="btn-primary" type="submit">
            Publish post <ArrowRight size={16} />
          </button>
        </div>

        <p className="mt-2 text-xs text-stone-400">Image limit: 2 MB per post.</p>
      </form>

      <div className="mx-auto mt-7 max-w-3xl space-y-5">
        {posts.length ? posts.map(post => {
          const isOwner = user?.email === post.email;
          const isEditing = editingId === post.id;

          return (
            <article
              key={post.id}
              className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm"
            >
              <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                  <div className="avatar">
                    {post.author?.[0]?.toUpperCase() || 'H'}
                  </div>

                  <div>
                    <p className="font-bold text-ink">{post.author}</p>
                    <p className="mt-1 text-xs text-stone-400">
                      {post.topic} · {new Date(post.date).toLocaleDateString()}
                    </p>
                  </div>
                </div>

                {isOwner ? (
                  <div className="flex gap-2">
                    <button
                      onClick={() => startEdit(post)}
                      className="btn-secondary !px-3 !py-2 text-xs"
                    >
                      Edit
                    </button>

                    <button
                      onClick={() => deletePost(post.id)}
                      className="btn-secondary !px-3 !py-2 text-xs text-red-600"
                    >
                      <Trash2 size={14} /> Delete
                    </button>
                  </div>
                ) : (
                  <ReportButton
                    type="community post"
                    id={post.id}
                    title={post.topic}
                    authorEmail={post.email}
                    currentUserEmail={user?.email}
                  />
                )}
              </div>

              {isEditing ? (
                <div className="mt-4 space-y-3">
                  <select
                    className="field"
                    value={editTopic}
                    onChange={e => setEditTopic(e.target.value)}
                  >
                    <option>Trail experience</option>
                    <option>Hiking tips</option>
                    <option>Questions for hikers</option>
                    <option>Photos and memories</option>
                  </select>

                  <textarea
                    className="field min-h-28"
                    value={editBody}
                    onChange={e => setEditBody(e.target.value)}
                  />

                  <div className="flex gap-2">
                    <button
                      onClick={() => saveEdit(post.id)}
                      className="btn-primary"
                    >
                      Save changes
                    </button>

                    <button
                      onClick={() => setEditingId(null)}
                      className="btn-secondary"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              ) : (
                <>
                  {post.body && (
                    <p className="mt-4 whitespace-pre-wrap text-sm leading-7 text-stone-700">
                      {post.body}
                    </p>
                  )}

                  {post.photo && (
                    <img
                      src={post.photo}
                      alt="Community hiking post"
                      className="mt-4 max-h-[480px] w-full rounded-xl object-cover"
                    />
                  )}
                </>
              )}

              <div className="mt-4 flex items-center gap-4 border-y border-stone-100 py-3">
                <button
                  type="button"
                  onClick={() => toggleLike(post.id)}
                  className={`flex items-center gap-2 text-sm font-semibold ${
                    (post.likes || []).includes(user?.email)
                      ? 'text-rose-600'
                      : 'text-stone-500'
                  }`}
                >
                  <Heart
                    size={18}
                    fill={(post.likes || []).includes(user?.email) ? 'currentColor' : 'none'}
                  />
                  Like · {(post.likes || []).length}
                </button>

                <span className="text-sm text-stone-400">
                  {(post.comments || []).length} comments
                </span>
              </div>

              <div className="mt-3 space-y-3">
                {(post.comments || []).map(comment => (
                  <div key={comment.id} className="rounded-xl bg-stone-50 px-3 py-2">
                    <p className="text-sm font-semibold text-ink">{comment.author}</p>
                    <p className="mt-1 text-sm text-stone-600">{comment.body}</p>
                  </div>
                ))}
              </div>

              <form
                onSubmit={e => addComment(e, post)}
                className="mt-3 flex gap-2"
              >
                <input
                  className="field"
                  value={commentDrafts[post.id] || ''}
                  onChange={e => setCommentDrafts({
                    ...commentDrafts,
                    [post.id]: e.target.value
                  })}
                  placeholder="Write a comment..."
                />

                <button className="btn-primary !px-4" type="submit">
                  Comment
                </button>
              </form>
            </article>
          );
        }) : (
          <div className="empty-state">
            <Users size={28} />
            <p className="mt-3 text-sm text-stone-500">
              No community posts yet. Be the first to share a hiking moment.
            </p>
          </div>
        )}
      </div>
    </main>
  );
}

// --------------------------------------------------
// TRAIL CARD
// --------------------------------------------------

function TrailCard({ trail }) {
  return (
    <Link
      to={`/trails/${trail.id}`}
      className="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-soft"
    >
      <div className="relative h-52 overflow-hidden">
        <img
          src={trail.image || imgFallback}
          onError={e => e.currentTarget.src = imgFallback}
          alt={trail.name}
          className="h-full w-full object-cover transition duration-700 group-hover:scale-105"
        />

        <span className="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-sage-700">
          {trail.region}
        </span>

        <span className="absolute bottom-4 right-4 rounded-full bg-ink/75 px-3 py-1 text-xs font-medium text-white">
          {trail.difficulty}
        </span>
      </div>

      <div className="p-5">
        <div className="flex items-start justify-between gap-3">
          <h3 className="text-lg font-bold text-ink">{trail.name}</h3>
          <ArrowUpRight size={18} className="mt-1 shrink-0 text-sage-600" />
        </div>

        <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-stone-500">
          <span className="flex items-center gap-1">
            <Footprints size={14} /> {trail.distance}
          </span>

          <span className="flex items-center gap-1">
            <Clock3 size={14} /> {trail.duration}
          </span>
        </div>

        <p className="mt-3 line-clamp-2 text-sm leading-6 text-stone-500">
          {trail.description}
        </p>

        <div className="mt-3">
          <ReportButton type="trail listing" id={trail.id} title={trail.name} />
        </div>
      </div>
    </Link>
  );
}

// --------------------------------------------------
// TRIP CARD
// Includes delete button for the trip owner.
// --------------------------------------------------

function TripCard({ trip, onJoin, joined, user, onDelete }) {
  const [copied, setCopied] = useState(false);

  const isOwner = user?.email && user.email === trip.createdBy;

  const copy = async e => {
    e.preventDefault();
    e.stopPropagation();

    try {
      await navigator.clipboard.writeText(trip.telegram || '');
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      window.prompt('Copy Telegram username', trip.telegram);
    }
  };

  return (
    <article className="rounded-2xl border border-stone-200 bg-white p-5 transition hover:shadow-soft">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <span className="rounded-full bg-sage-50 px-3 py-1 text-xs font-semibold text-sage-700">
          {trip.difficulty} · {trip.region}
        </span>

        <span className="text-xs text-stone-500">
          {trip.joined || 0}/{trip.spots} spots
        </span>
      </div>

      <h3 className="mt-4 text-lg font-bold text-ink">{trip.title}</h3>

      <p className="mt-2 flex items-center gap-2 text-sm text-stone-500">
        <MapPin size={15} /> {trip.meeting || trip.mountain}
      </p>

      <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
        <span className="flex items-center gap-2 text-stone-600">
          <CalendarDays size={15} className="text-sage-600" />
          {trip.date}
        </span>

        <span className="flex items-center gap-2 text-stone-600">
          <Clock3 size={15} className="text-sage-600" />
          {trip.time}
        </span>

        <span className="flex items-center gap-2 text-stone-600">
          <Gauge size={15} className="text-sage-600" />
          {trip.pace} pace
        </span>

        <span className="flex items-center gap-2 text-stone-600">
          <Users size={15} className="text-sage-600" />
          {trip.organizer}
        </span>
      </div>

      <p className="mt-4 line-clamp-2 text-sm leading-6 text-stone-500">
        {trip.description}
      </p>

      <div className="mt-5 flex flex-wrap gap-2 border-t border-stone-100 pt-4">
        {!isOwner && (
          <ReportButton
            type="trip post"
            id={trip.id}
            title={trip.title}
            authorEmail={trip.createdBy}
            currentUserEmail={user?.email}
          />
        )}

        <button
          onClick={copy}
          className="btn-secondary !px-3 !py-2 text-xs"
        >
          {copied ? <Check size={14} /> : <Copy size={14} />}
          {copied ? 'Copied' : trip.telegram || 'Telegram'}
        </button>

        {trip.telegram && (
          <a
            href={`https://t.me/${trip.telegram.replace('@', '')}`}
            target="_blank"
            rel="noreferrer"
            className="btn-secondary !px-3 !py-2 text-xs"
          >
            <Send size={14} /> Open Telegram
          </a>
        )}

        <button
          disabled={joined || trip.joined >= trip.spots}
          onClick={() => onJoin(trip.id)}
          className="btn-primary ml-auto !px-4 !py-2 text-xs disabled:cursor-not-allowed disabled:opacity-50"
        >
          {joined ? 'Joined ✓' : trip.joined >= trip.spots ? 'Full' : 'Join trip'}
        </button>

        {isOwner && (
          <button
            onClick={() => onDelete(trip.id)}
            className="btn-secondary !px-3 !py-2 text-xs text-red-600"
          >
            <Trash2 size={14} /> Delete
          </button>
        )}
      </div>
    </article>
  );
}

// --------------------------------------------------
// HOME PAGE
// --------------------------------------------------

function Home({ trips, user, onJoin, joined }) {
  return (
    <>
      <section className="container-shell pt-5">
        <div className="relative min-h-[570px] overflow-hidden rounded-[26px] bg-[#e7e8df]">
          <img
            src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=2200&q=90"
            alt="Mountain peaks"
            className="absolute inset-0 h-full w-full object-cover"
            onError={e => e.currentTarget.src = imgFallback}
          />

          <div className="absolute inset-0 bg-gradient-to-r from-white via-white/85 to-white/5" />

          <div className="relative grid min-h-[570px] content-start gap-8 px-6 py-12 sm:px-10 md:grid-cols-[1.15fr_.75fr] md:px-14 md:py-16">
            <div className="max-w-2xl">
              <p className="eyebrow">YOUR NEXT ADVENTURE STARTS HERE</p>

              <h1 className="mt-5 text-5xl font-black leading-[.98] tracking-[-.055em] text-sage-600 sm:text-6xl lg:text-[76px]">
                THE MOUNTAINS<br />ARE CALLING<span className="text-ink">.</span>
              </h1>

              <p className="mt-6 max-w-md text-sm leading-6 text-stone-600 sm:text-base">
                Find beautiful trails, join local hiking buddies, and turn your next day off into a story worth telling.
              </p>

              <div className="mt-7 flex flex-wrap gap-3">
                <Link to="/trails" className="btn-primary">
                  Explore trails <ArrowRight size={17} />
                </Link>

                <Link to="/trails?create=true" className="btn-secondary">
                  Organise a trip
                </Link>
              </div>

              <div className="mt-9 flex items-center gap-4 text-xs font-medium text-stone-600">
                <div className="flex -space-x-2">
                  <span className="mini-avatar bg-[#d5b49c]">M</span>
                  <span className="mini-avatar bg-[#aab6a1]">K</span>
                  <span className="mini-avatar bg-[#d9c77e]">T</span>
                  <span className="mini-avatar bg-[#a3b9c1]">+</span>
                </div>

                <span>Small groups. Big memories.</span>
              </div>
            </div>

            <div className="mt-0 max-w-sm self-start rounded-2xl border border-white/70 bg-white/75 p-5 shadow-soft backdrop-blur-sm md:justify-self-end">
              <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[.14em] text-sage-700">
                <Compass size={16} /> Find your match
              </div>

              <p className="mt-3 text-sm leading-6 text-stone-600">
                Choose a region and a difficulty to find a trail that fits your pace and experience.
              </p>

              <Link
                to="/trails?match=true"
                className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-sage-700"
              >
                Find matching trails <ArrowRight size={15} />
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section className="container-shell section-pad">
        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <p className="eyebrow">GET OUT THERE</p>
            <h2 className="section-title">Popular mountains</h2>
            <p className="mt-2 text-sm text-stone-500">
              A few local favourites to get your boots moving.
            </p>
          </div>

          <Link to="/trails" className="text-sm font-bold text-sage-700">
            View all trails <ArrowRight size={16} />
          </Link>
        </div>

        <div className="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {trails.slice(0, 3).map(trail => (
            <TrailCard key={trail.id} trail={trail} />
          ))}
        </div>
      </section>

      <section className="container-shell">
        <div className="overflow-hidden rounded-3xl bg-sage-50 px-6 py-9 sm:px-10 md:flex md:items-center md:justify-between">
          <div>
            <p className="eyebrow">GOOD PEOPLE, GREAT OUTDOORS</p>
            <h2 className="mt-3 text-2xl font-bold tracking-tight text-ink sm:text-3xl">
              The best views are shared.
            </h2>
            <p className="mt-2 max-w-xl text-sm leading-6 text-stone-600">
              Meet a new crew, discover a route, and make a plan for the weekend.
            </p>
          </div>

          <Link to="/trails?create=true" className="btn-primary mt-6 md:mt-0">
            Post your trip <ArrowRight size={16} />
          </Link>
        </div>
      </section>

      <section className="container-shell section-pad">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="eyebrow">COMMUNITY PLANS</p>
            <h2 className="section-title">Upcoming group trips</h2>
          </div>

          <Link to="/trails" className="text-sm font-bold text-sage-700">
            Explore all <ArrowRight size={15} />
          </Link>
        </div>

        <div className="mt-7 grid gap-5 lg:grid-cols-2">
          {trips.slice(0, 2).map(trip => (
            <TripCard
              key={trip.id}
              trip={trip}
              user={user}
              onJoin={onJoin}
              joined={joined.includes(trip.id)}
            />
          ))}
        </div>
      </section>
    </>
  );
}

// --------------------------------------------------
// TRAILS PAGE
// Mountain matching + trip creation + group trips.
// --------------------------------------------------

function TrailsPage({ trips, onJoin, joined, user, setTrips, onDeleteTrip }) {
  const params = new URLSearchParams(window.location.search);

  const [region, setRegion] = useState(params.get('region') || 'All regions');
  const [difficulty, setDifficulty] = useState('All levels');
  const [query, setQuery] = useState('');
  const [fitness, setFitness] = useState('Intermediate');
  const [pace, setPace] = useState('Steady');
  const [showMatches, setShowMatches] = useState(params.get('match') === 'true');
  const [showForm, setShowForm] = useState(params.get('create') === 'true');

  const approvedPlaces = stored('hb-approved-places', []);
  const allTrails = [...trails, ...approvedPlaces];

  const availableRegions = [
    ...new Set([...regions, ...approvedPlaces.map(place => place.region)])
  ];

  const filteredTrails = allTrails.filter(trail =>
    (region === 'All regions' || trail.region === region) &&
    (difficulty === 'All levels' || trail.difficulty === difficulty) &&
    trail.name.toLowerCase().includes(query.toLowerCase())
  );

  const matchedTrails = filteredTrails.filter(trail =>
    (
      fitness === 'Beginner'
        ? trail.difficulty === 'Easy' || trail.difficulty === 'Moderate'
        : fitness === 'Intermediate'
          ? trail.difficulty !== 'Hard'
          : true
    ) &&
    (pace === 'Any pace' || trail.pace === pace)
  );

  const visibleTrails = showMatches ? matchedTrails : filteredTrails;

  return (
    <main className="container-shell page-pad">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div className="max-w-2xl">
          <p className="eyebrow">YOUR NEXT OUTDOOR DAY</p>

          <h1 className="page-title">
            Find your kind of <span className="text-sage-600">wild.</span>
          </h1>

          <p className="mt-4 leading-7 text-stone-500">
            Choose a mountain to see its group trips. Find a hiking buddy or organise your own adventure.
          </p>
        </div>

        <button
          onClick={() => {
            if (!user) {
              window.location.href = '/login?next=/trails';
              return;
            }

            setShowForm(!showForm);
          }}
          className="btn-primary shrink-0"
        >
          {showForm ? 'Close form' : '＋ Post your trip'}
        </button>
      </div>

      {showForm && (
        <div className="mt-7 rounded-3xl border border-stone-200 bg-white p-5 shadow-soft sm:p-8">
          <PostTrip
            user={user}
            trips={trips}
            setTrips={setTrips}
            onCancel={() => setShowForm(false)}
          />
        </div>
      )}

      <div className="mt-7 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 md:grid-cols-[1fr_220px_200px]">
        <label className="input-wrap">
          <Search size={17} />
          <input
            value={query}
            onChange={e => setQuery(e.target.value)}
            placeholder="Search a mountain or trail..."
          />
        </label>

        <select
          aria-label="Filter by region"
          className="field"
          value={region}
          onChange={e => setRegion(e.target.value)}
        >
          {availableRegions.map(item => (
            <option key={item}>{item}</option>
          ))}
        </select>

        <select
          aria-label="Filter by difficulty"
          className="field"
          value={difficulty}
          onChange={e => setDifficulty(e.target.value)}
        >
          {levels.map(item => (
            <option key={item}>{item}</option>
          ))}
        </select>
      </div>

      <section className="mt-6 rounded-2xl bg-sage-50 p-5">
        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <p className="eyebrow">PERSONALISED DISCOVERY</p>
            <h2 className="mt-1 text-xl font-bold text-ink">
              Find mountains that match you
            </h2>
            <p className="mt-1 text-sm text-stone-600">
              Adjust your preferences to find suitable trails.
            </p>
          </div>

          <button
            onClick={() => setShowMatches(!showMatches)}
            className="btn-secondary"
          >
            {showMatches ? 'Show all mountains' : 'Show matching mountains'}
            <Heart size={15} />
          </button>
        </div>

        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <label className="field-label">
            Fitness level

            <select
              className="field mt-2"
              value={fitness}
              onChange={e => {
                setFitness(e.target.value);
                setShowMatches(true);
              }}
            >
              <option>Beginner</option>
              <option>Intermediate</option>
              <option>Advanced</option>
            </select>
          </label>

          <label className="field-label">
            Preferred pace

            <select
              className="field mt-2"
              value={pace}
              onChange={e => {
                setPace(e.target.value);
                setShowMatches(true);
              }}
            >
              <option>Any pace</option>
              <option>Relaxed</option>
              <option>Steady</option>
              <option>Fast</option>
            </select>
          </label>
        </div>
      </section>

      <div className="mt-8 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold text-ink">
            {showMatches ? 'Mountains picked for you' : 'Explore mountains'}
          </h2>

          <p className="mt-1 text-sm text-stone-500">
            {visibleTrails.length} mountains found
          </p>
        </div>

        <span className="hidden items-center gap-2 text-xs text-stone-400 sm:flex">
          <SlidersHorizontal size={14} /> Filters applied
        </span>
      </div>

      <div className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {visibleTrails.map(trail => (
          <TrailCard key={trail.id} trail={trail} />
        ))}
      </div>

      {visibleTrails.length === 0 && (
        <div className="empty-state">
          <Mountain size={30} />
          <h3 className="mt-3 font-bold text-ink">No mountains match yet</h3>
          <p className="mt-2 text-sm text-stone-500">
            Try another region, difficulty or matching preference.
          </p>

          <button
            className="btn-secondary mt-4"
            onClick={() => {
              setRegion('All regions');
              setDifficulty('All levels');
              setQuery('');
              setShowMatches(false);
            }}
          >
            Clear filters
          </button>
        </div>
      )}

      <section className="mt-12">
        <p className="eyebrow">COMMUNITY PLANS</p>
        <h2 className="section-title">Upcoming group trips</h2>

        <p className="mt-2 text-sm text-stone-500">
          Discover trips created by other hikers.
        </p>

        <div className="mt-6 grid gap-5 lg:grid-cols-2">
          {trips.length ? trips.map(trip => (
            <TripCard
              key={trip.id}
              trip={trip}
              user={user}
              onJoin={onJoin}
              joined={joined.includes(trip.id)}
              onDelete={onDeleteTrip}
            />
          )) : (
            <div className="empty-state lg:col-span-2">
              <TentTree size={28} />
              <p className="mt-3 text-sm text-stone-500">
                No group trips have been posted yet.
              </p>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}

// --------------------------------------------------
// TRIP CREATION FORM
// --------------------------------------------------

function PostTrip({ user, trips, setTrips, onCancel }) {
  const nav = useNavigate();

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  const defaultDate = tomorrow.toISOString().slice(0, 10);

  const [error, setError] = useState('');

  const [form, setForm] = useState({
    title: '',
    mountain: trails[0]?.name || 'Mount Popa',
    region: trails[0]?.region || 'Bagan',
    date: defaultDate,
    time: '07:00',
    difficulty: 'Moderate',
    pace: 'Steady',
    fitness: 'Intermediate',
    spots: '8',
    meeting: '',
    vehicle: 'Car pool',
    telegram: '',
    phone: '',
    emergency: '',
    description: '',
    customMountain: '',
    customRegion: ''
  });

  const change = e => {
    setForm({
      ...form,
      [e.target.name]: e.target.value
    });
  };

  const submit = e => {
    e.preventDefault();

    if (!user) {
      nav('/login?next=/trails');
      return;
    }

    if (new Date(form.date + 'T' + form.time) < new Date()) {
      setError('Choose a future date and time for the trip.');
      return;
    }

    if (!form.title.trim() || !form.meeting.trim()) {
      setError('Please complete the trip title and meeting point.');
      return;
    }

    const finalMountain = form.customMountain.trim() || form.mountain;
    const finalRegion = form.customRegion.trim() || form.region;

    if (form.customMountain.trim() || form.customRegion.trim()) {
      const suggestions = stored('hb-place-suggestions', []);

      suggestions.unshift({
        id: Date.now() + 1,
        name: finalMountain,
        region: finalRegion,
        difficulty: form.difficulty,
        description: form.description,
        submittedBy: user.name,
        email: user.email,
        status: 'Pending'
      });

      save('hb-place-suggestions', suggestions);
    }

    const trip = {
      ...form,
      mountain: finalMountain,
      region: finalRegion,
      id: Date.now(),
      organizer: user.name,
      joined: 0,
      spots: Number(form.spots),
      createdBy: user.email
    };

    const updated = [trip, ...trips];

    setTrips(updated);
    save('hb-trips', updated);

    if (onCancel) onCancel();

    nav('/trails');
  };

  return (
    <section>
      <div className="max-w-2xl">
        <p className="eyebrow">BRING THE CREW TOGETHER</p>

        <h2 className="text-2xl font-bold text-ink">
          Post a new <span className="text-sage-600">trip.</span>
        </h2>

        <p className="mt-2 leading-7 text-stone-500">
          Share your plan, set the pace and invite other hikers.
        </p>
      </div>

      <form onSubmit={submit} className="mt-6 max-w-4xl">
        <div className="form-section-title">
          <span className="step-number">01</span>

          <div>
            <h2>Trip details</h2>
            <p>Tell the community what you have planned.</p>
          </div>
        </div>

        <div className="form-grid">
          <Field
            label="Trip title"
            name="title"
            value={form.title}
            onChange={change}
            placeholder="e.g. Sunrise at Mount Popa"
            required
          />

          <Field
            label="Mountain / trail"
            name="mountain"
            value={form.mountain}
            onChange={e => {
              const selected = trails.find(t => t.name === e.target.value);

              setForm({
                ...form,
                mountain: e.target.value,
                region: selected?.region || form.region
              });
            }}
            options={[
              ...new Set([
                ...trails.map(t => t.name),
                '＋ Add a new mountain / trail'
              ])
            ]}
          />

          <Field
            label="Region"
            name="region"
            value={form.region}
            onChange={change}
            options={[...regions.slice(1), '＋ Add a new region']}
          />

          {(form.mountain === '＋ Add a new mountain / trail' ||
            form.region === '＋ Add a new region') && (
            <div className="form-grid col-span-full rounded-xl bg-sage-50 p-4">
              <p className="field-label col-span-full">
                Add a place not listed above
              </p>

              {form.mountain === '＋ Add a new mountain / trail' && (
                <Field
                  label="New mountain / trail name"
                  name="customMountain"
                  value={form.customMountain}
                  onChange={change}
                  placeholder="Enter mountain or trail name"
                  required
                />
              )}

              {form.region === '＋ Add a new region' && (
                <Field
                  label="New region / township"
                  name="customRegion"
                  value={form.customRegion}
                  onChange={change}
                  placeholder="Enter region or township"
                  required
                />
              )}

              <p className="col-span-full text-xs leading-5 text-stone-500">
                New place details will be sent to the administrator for review.
              </p>
            </div>
          )}

          <Field
            label="Difficulty"
            name="difficulty"
            value={form.difficulty}
            onChange={change}
            options={levels.slice(1)}
          />

          <Field
            label="Date"
            type="date"
            name="date"
            value={form.date}
            onChange={change}
            required
          />

          <Field
            label="Start time"
            type="time"
            name="time"
            value={form.time}
            onChange={change}
            required
          />

          <Field
            label="Preferred pace"
            name="pace"
            value={form.pace}
            onChange={change}
            options={['Relaxed', 'Steady', 'Fast']}
          />

          <Field
            label="Fitness level"
            name="fitness"
            value={form.fitness}
            onChange={change}
            options={['Beginner', 'Intermediate', 'Advanced']}
          />

          <Field
            label="Available spots"
            type="number"
            name="spots"
            min="1"
            max="30"
            value={form.spots}
            onChange={change}
            required
          />

          <Field
            label="Transport plan"
            name="vehicle"
            value={form.vehicle}
            onChange={change}
            options={['Car pool', 'Own transport', 'Bus / shared taxi', 'Hiking only']}
          />

          <Field
            label="Meeting point"
            name="meeting"
            value={form.meeting}
            onChange={change}
            placeholder="A clear landmark or entrance"
            required
          />

          <Field
            label="Telegram username"
            name="telegram"
            value={form.telegram}
            onChange={change}
            placeholder="@yourusername"
          />

          <Field
            label="Contact phone"
            name="phone"
            value={form.phone}
            onChange={change}
            placeholder="09 xxx xxx xxx"
          />

          <Field
            label="Emergency contact"
            name="emergency"
            value={form.emergency}
            onChange={change}
            placeholder="Name and phone number"
          />
        </div>

        <div className="mt-5">
          <label className="field-label">
            Trip description

            <textarea
              className="field mt-2 min-h-28 resize-y"
              name="description"
              value={form.description}
              onChange={change}
              placeholder="Describe the route, what to bring and any important notes..."
              required
            />
          </label>
        </div>

        {error && (
          <p role="alert" className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {error}
          </p>
        )}

        <div className="mt-7 flex flex-col-reverse justify-between gap-3 border-t border-stone-100 pt-6 sm:flex-row sm:items-center">
          <p className="max-w-md text-xs leading-5 text-stone-400">
            Please confirm your meeting point and safety details before publishing.
          </p>

          <div className="flex gap-2">
            {onCancel && (
              <button
                type="button"
                onClick={onCancel}
                className="btn-secondary"
              >
                Cancel
              </button>
            )}

            <button className="btn-primary" type="submit">
              Publish trip <ArrowRight size={16} />
            </button>
          </div>
        </div>
      </form>
    </section>
  );
}

// --------------------------------------------------
// REUSABLE FIELD
// --------------------------------------------------

function Field({
  label,
  name,
  value,
  onChange,
  options,
  type = 'text',
  ...rest
}) {
  return (
    <label className="field-label">
      {label}

      {options ? (
        <select
          className="field mt-2"
          name={name}
          value={value}
          onChange={onChange}
          {...rest}
        >
          {options.map(option => (
            <option key={option}>{option}</option>
          ))}
        </select>
      ) : (
        <input
          className="field mt-2"
          type={type}
          name={name}
          value={value}
          onChange={onChange}
          {...rest}
        />
      )}
    </label>
  );
}

// --------------------------------------------------
// TRAIL DETAIL PAGE
// --------------------------------------------------

function TrailDetail({ trips, onJoin, joined, user, onDeleteTrip }) {
  const { id } = useParams();

  const trail = [...trails, ...stored('hb-approved-places', [])]
    .find(item => String(item.id) === String(id));

  if (!trail) return <NotFound />;

  const related = trips.filter(
    trip =>
      trip.mountain === trail.name ||
      trip.title?.toLowerCase().includes(trail.name.toLowerCase())
  );

  return (
    <main className="container-shell page-pad">
      <Link to="/trails" className="text-sm font-semibold text-sage-700">
        ← Back to trails
      </Link>

      <div className="mt-5 overflow-hidden rounded-3xl bg-white shadow-soft">
        <img
          src={trail.image || imgFallback}
          alt={trail.name}
          className="h-72 w-full object-cover md:h-[420px]"
        />

        <div className="p-6 sm:p-9">
          <p className="eyebrow">{trail.region}</p>

          <h1 className="page-title">{trail.name}</h1>

          <p className="mt-4 leading-7 text-stone-600">
            {trail.description}
          </p>

          <div className="mt-6 flex flex-wrap gap-5 text-sm text-stone-500">
            <span><Footprints size={15} className="mr-1 inline" />{trail.distance}</span>
            <span><Clock3 size={15} className="mr-1 inline" />{trail.duration}</span>
            <span><Gauge size={15} className="mr-1 inline" />{trail.difficulty}</span>
          </div>

          <Link to="/trails?create=true" className="btn-primary mt-6">
            Organise this trail <ArrowRight size={16} />
          </Link>
        </div>
      </div>

      <section className="mt-10">
        <h2 className="text-xl font-bold text-ink">Trips on this trail</h2>

        <p className="mt-2 text-sm text-stone-500">
          Find group trips created for this mountain.
        </p>

        <div className="mt-5 grid gap-5 lg:grid-cols-2">
          {related.length ? related.map(trip => (
            <TripCard
              key={trip.id}
              trip={trip}
              user={user}
              onJoin={onJoin}
              joined={joined.includes(trip.id)}
              onDelete={onDeleteTrip}
            />
          )) : (
            <div className="empty-state lg:col-span-2">
              <TentTree size={28} />
              <p className="mt-3 text-sm text-stone-500">
                No trips are posted for this trail yet.
              </p>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}

// --------------------------------------------------
// PROFILE PAGE
// My Posts and My Trails tabs.
// --------------------------------------------------

function Profile({ user, setUser, trips, onDeleteTrip }) {
  const [edit, setEdit] = useState(false);
  const [activeTab, setActiveTab] = useState('posts');

  const [form, setForm] = useState(() => ({ ...user }));

  const [posts, setPosts] = useState(
    () => stored('hb-community-posts', [])
  );

  if (!user) {
    return <Navigate to="/login?next=%2Fprofile" replace />;
  }

  const update = e => {
    setForm({
      ...form,
      [e.target.name]: e.target.value
    });
  };

  
    const saveProfile = async e => {
    e.preventDefault();

    try {
        const token = getAuthToken();

        if (!token) {
        throw new Error('Please log in again.');
        }

        const response = await fetch(`${API_BASE}/profile`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
            name: form.name,
            city: form.city,
            fitness_level: form.fitness,
            preferred_pace: form.pace
        })
        });

        const data = await response.json();

        if (!response.ok) {
        const validationErrors = data.errors
            ? Object.values(data.errors).flat().join(' ')
            : '';

        throw new Error(
            validationErrors ||
            data.message ||
            'Unable to update profile.'
        );
        }

        // Convert Laravel response to the React user format
        const updatedUser = mapApiUser(data.user);

        // Update React state and local cache only after MySQL succeeds
        setUser(updatedUser);
        save('hb-user', updatedUser);
        setForm(updatedUser);
        setEdit(false);

        alert('Your profile has been updated successfully.');

    } catch (error) {
        alert(error.message);
    }
    };


  const myPosts = posts.filter(post => post.email === user.email);

  const myTrails = trips.filter(
    trip => trip.createdBy === user.email
  );

  const deletePost = id => {
    if (!window.confirm('Delete this post?')) return;

    const updated = posts.filter(
      post => !(post.id === id && post.email === user.email)
    );

    setPosts(updated);
    save('hb-community-posts', updated);
  };

  return (
    <main className="container-shell page-pad">
      <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
          <p className="eyebrow">YOUR OUTDOOR STORY</p>
          <h1 className="page-title">Member profile</h1>
        </div>

        <button className="btn-secondary" onClick={() => setEdit(!edit)}>
          <Settings size={16} />
          {edit ? 'Cancel editing' : 'Edit preferences'}
        </button>
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
        <section className="rounded-3xl bg-sage-50 p-6 sm:p-8">
          <div className="flex items-center gap-4">
            <div className="grid h-16 w-16 place-items-center rounded-full bg-sage-600 text-2xl font-bold text-white">
              {(user.name || 'H')[0].toUpperCase()}
            </div>

            <div>
              <h2 className="text-xl font-bold text-ink">{user.name}</h2>
              <p className="mt-1 flex items-center gap-1.5 text-sm text-stone-500">
                <Mail size={14} /> {user.email}
              </p>
            </div>
          </div>

          <div className="mt-7 grid grid-cols-2 gap-3">
            <Stat label="Posts created" value={myPosts.length} />
            <Stat label="Trips organised" value={myTrails.length} />
          </div>

          {edit ? (
            <form onSubmit={saveProfile} className="mt-7 space-y-4">
              <Field
                label="Full name"
                name="name"
                value={form.name || ''}
                onChange={update}
                required
              />

              <Field
                label="City / region"
                name="city"
                value={form.city || 'Mandalay'}
                onChange={update}
                options={regions.slice(1)}
              />

              <Field
                label="Fitness level"
                name="fitness"
                value={form.fitness || 'Intermediate'}
                onChange={update}
                options={['Beginner', 'Intermediate', 'Advanced']}
              />

              <Field
                label="Preferred pace"
                name="pace"
                value={form.pace || 'Steady'}
                onChange={update}
                options={['Relaxed', 'Steady', 'Fast']}
              />

              <button className="btn-primary w-full" type="submit">
                Save preferences <Check size={16} />
              </button>
            </form>
          ) : (
            <div className="mt-7 space-y-4">
              <ProfileRow icon={<MapPin />} label="Home region" value={user.city || 'Mandalay'} />
              <ProfileRow icon={<Gauge />} label="Fitness level" value={user.fitness || 'Intermediate'} />
              <ProfileRow icon={<Footprints />} label="Preferred pace" value={user.pace || 'Steady'} />
              <ProfileRow icon={<BadgeCheck />} label="Achievements" value="First steps · Community explorer" />
            </div>
          )}
        </section>

        <section>
          <h2 className="text-xl font-bold text-ink">Your activity</h2>

          <p className="mt-1 text-sm text-stone-500">
            Manage your community posts and created trails.
          </p>

          <div className="mt-5 flex gap-2 border-b border-stone-200">
            <button
              onClick={() => setActiveTab('posts')}
              className={`px-4 py-3 text-sm font-semibold ${
                activeTab === 'posts'
                  ? 'border-b-2 border-sage-600 text-sage-700'
                  : 'text-stone-500'
              }`}
            >
              My Posts ({myPosts.length})
            </button>

            <button
              onClick={() => setActiveTab('trails')}
              className={`px-4 py-3 text-sm font-semibold ${
                activeTab === 'trails'
                  ? 'border-b-2 border-sage-600 text-sage-700'
                  : 'text-stone-500'
              }`}
            >
              My Trails ({myTrails.length})
            </button>
          </div>

          {activeTab === 'posts' && (
            <div className="mt-5 space-y-4">
              {myPosts.length ? myPosts.map(post => (
                <article
                  key={post.id}
                  className="rounded-2xl border border-stone-200 p-5"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="text-xs font-semibold text-sage-600">
                        {post.topic}
                      </p>

                      <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-stone-700">
                        {post.body}
                      </p>

                      {post.photo && (
                        <img
                          src={post.photo}
                          alt="My hiking post"
                          className="mt-3 max-h-52 rounded-xl object-cover"
                        />
                      )}

                      <p className="mt-3 text-xs text-stone-400">
                        {new Date(post.date).toLocaleDateString()}
                      </p>
                    </div>

                    <button
                      onClick={() => deletePost(post.id)}
                      className="icon-button text-red-500"
                      aria-label="Delete post"
                    >
                      <Trash2 size={17} />
                    </button>
                  </div>

                  <Link
                    to="/community"
                    className="mt-3 inline-block text-sm font-semibold text-sage-700"
                  >
                    View in Community →
                  </Link>
                </article>
              )) : (
                <div className="empty-state">
                  <Users size={28} />
                  <p className="mt-3 text-sm text-stone-500">
                    You have not created any community posts yet.
                  </p>

                  <Link to="/community" className="btn-primary mt-4">
                    Create a post
                  </Link>
                </div>
              )}
            </div>
          )}

          {activeTab === 'trails' && (
            <div className="mt-5 space-y-4">
              {myTrails.length ? myTrails.map(trip => (
                <div
                  key={trip.id}
                  className="rounded-2xl border border-stone-200 p-5"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wider text-sage-600">
                        {trip.date} · {trip.region}
                      </p>

                      <h3 className="mt-2 font-bold text-ink">
                        {trip.title}
                      </h3>

                      <p className="mt-1 text-sm text-stone-500">
                        {trip.mountain}
                      </p>

                      <p className="mt-1 text-sm text-stone-500">
                        {trip.joined || 0} / {trip.spots} attendees joined
                      </p>
                    </div>

                    <button
                      onClick={() => onDeleteTrip(trip.id)}
                      className="icon-button text-red-500"
                      aria-label="Delete trip"
                    >
                      <Trash2 size={17} />
                    </button>
                  </div>
                </div>
              )) : (
                <div className="empty-state">
                  <TentTree size={28} />
                  <p className="mt-3 text-sm text-stone-500">
                    You have not created any trips yet.
                  </p>

                  <Link to="/trails?create=true" className="btn-primary mt-4">
                    Create a trip
                  </Link>
                </div>
              )}

              <Link to="/trails?create=true" className="btn-primary mt-3">
                <Plus size={16} /> Create a trip
              </Link>
            </div>
          )}
        </section>
      </div>
    </main>
  );
}

// --------------------------------------------------
// PROFILE ROW AND STAT
// --------------------------------------------------

function ProfileRow({ icon, label, value }) {
  return (
    <div className="flex items-center gap-3">
      <span className="text-sage-600 [&>svg]:h-[17px] [&>svg]:w-[17px]">
        {icon}
      </span>

      <div className="flex-1">
        <p className="text-xs text-stone-400">{label}</p>
        <p className="mt-0.5 text-sm font-semibold text-ink">{value}</p>
      </div>
    </div>
  );
}

function Stat({ label, value }) {
  return (
    <div className="rounded-xl border border-white bg-white/70 p-4">
      <p className="text-xs text-stone-500">{label}</p>
      <p className="mt-1 text-xl font-bold text-ink">{value}</p>
    </div>
  );
}

// --------------------------------------------------
// ORGANIZER DASHBOARD
// --------------------------------------------------

function Dashboard({ user, trips, setTrips, onDeleteTrip }) {
  const [selected, setSelected] = useState(null);

  if (!user) {
    return <Navigate to="/login?next=%2Fdashboard" replace />;
  }

  const mine = trips.filter(trip => trip.createdBy === user.email);
  const selectedTrip = trips.find(trip => trip.id === selected);

  return (
    <main className="container-shell page-pad">
      <p className="eyebrow">YOUR COMMUNITY, YOUR PLANS</p>
      <h1 className="page-title">Organizer dashboard</h1>

      <p className="mt-3 text-stone-500">
        Manage your trips and keep track of attendees.
      </p>

      <div className="mt-8 grid gap-4 sm:grid-cols-3">
        <Stat label="Trips organised" value={mine.length} />

        <Stat
          label="Total attendees"
          value={mine.reduce((total, trip) => total + (Number(trip.joined) || 0), 0)}
        />

        <Stat
          label="Upcoming trips"
          value={mine.filter(trip => trip.date >= new Date().toISOString().slice(0, 10)).length}
        />
      </div>

      <div className="mt-8 flex items-center justify-between">
        <h2 className="text-xl font-bold">Your trips</h2>

        <Link to="/trails?create=true" className="btn-primary">
          <Plus size={16} /> Create trip
        </Link>
      </div>

      <div className="mt-4 space-y-3">
        {mine.length ? mine.map(trip => (
          <div
            key={trip.id}
            className="flex flex-col justify-between gap-4 rounded-2xl border border-stone-200 p-5 sm:flex-row sm:items-center"
          >
            <div>
              <p className="text-xs text-sage-600">
                {trip.date} · {trip.region}
              </p>

              <h3 className="mt-1 font-bold text-ink">{trip.title}</h3>

              <p className="mt-1 text-sm text-stone-500">
                {trip.joined || 0} joined · {trip.spots} spaces
              </p>
            </div>

            <div className="flex gap-2">
              <button
                className="btn-secondary"
                onClick={() => setSelected(trip.id)}
              >
                Manage attendees
              </button>

              <button
                aria-label="Delete trip"
                className="icon-button text-red-500"
                onClick={() => onDeleteTrip(trip.id)}
              >
                <Trash2 size={17} />
              </button>
            </div>
          </div>
        )) : (
          <div className="empty-state">
            <LayoutDashboard size={28} />

            <p className="mt-3 text-sm text-stone-500">
              No trips to manage. Publish your first trip to get started.
            </p>

            <Link to="/trails?create=true" className="btn-primary mt-4">
              Post a trip
            </Link>
          </div>
        )}
      </div>

      {selectedTrip && (
        <div className="modal-backdrop" onClick={() => setSelected(null)}>
          <div
            role="dialog"
            aria-modal="true"
            className="modal-card"
            onClick={e => e.stopPropagation()}
          >
            <div className="flex items-start justify-between">
              <div>
                <p className="eyebrow">ATTENDEE MANAGEMENT</p>
                <h2 className="mt-2 text-xl font-bold">{selectedTrip.title}</h2>

                <p className="mt-1 text-sm text-stone-500">
                  {selectedTrip.joined || 0} attendees · {selectedTrip.date}
                </p>
              </div>

              <button className="icon-button" onClick={() => setSelected(null)}>
                <X />
              </button>
            </div>

            <div className="mt-5 rounded-xl bg-sage-50 p-4">
              <p className="text-sm font-semibold">Organizer contact details</p>

              <p className="mt-2 text-sm text-stone-600">
                Phone: {selectedTrip.phone || 'Not provided'}
              </p>

              <p className="mt-1 text-sm text-stone-600">
                Emergency contact: {selectedTrip.emergency || 'Not provided'}
              </p>

              <p className="mt-1 text-sm text-stone-600">
                Transport: {selectedTrip.vehicle}
              </p>
            </div>

            <h3 className="mt-5 font-semibold">Joined members</h3>

            <p className="mt-2 text-sm leading-6 text-stone-500">
              This starter uses demo data and local browser storage. Real attendee names and contact details require backend registration and join records.
            </p>

            <div className="mt-5 flex justify-end">
              <button className="btn-primary" onClick={() => setSelected(null)}>
                Done
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}

// --------------------------------------------------
// AUTHENTICATION
// --------------------------------------------------


function AuthPage({ mode, setUser }) {
  const register = mode === 'register';
  const nav = useNavigate();

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    city: 'Mandalay'
  });

  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const change = e => {
    setForm({
      ...form,
      [e.target.name]: e.target.value
    });
  };

  const submit = async e => {
    e.preventDefault();
    setError('');

    const email = form.email.trim().toLowerCase();

    if (form.password.length < 8) {
      setError('Use at least 8 characters for the password.');
      return;
    }

    if (register && !form.name.trim()) {
      setError('Enter your full name.');
      return;
    }

    if (register && form.password !== form.password_confirmation) {
      setError('The passwords do not match.');
      return;
    }

    setLoading(true);

    try {
      const endpoint = register ? '/register' : '/login';

      const payload = register
        ? {
            name: form.name.trim(),
            email,
            password: form.password,
            password_confirmation: form.password_confirmation,
            city: form.city,
            fitness_level: 'Intermediate',
            preferred_pace: 'Steady'
          }
        : {
            email,
            password: form.password
          };

      const response = await fetch(`${API_BASE}${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok) {
        const validationErrors = data.errors
          ? Object.values(data.errors).flat().join(' ')
          : '';

        throw new Error(
          validationErrors ||
          data.message ||
          'Something went wrong. Please try again.'
        );
      }

      localStorage.setItem('hb-token', data.token);

      const newUser = mapApiUser(data.user);

      localStorage.setItem('hb-user', JSON.stringify(newUser));

      setUser(newUser);

      const next = new URLSearchParams(window.location.search).get('next');

      nav(next || '/profile', { replace: true });

    } catch (err) {
      setError(
        err.message === 'Failed to fetch'
          ? 'Cannot connect to the server. Please make sure Laravel is running.'
          : err.message
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="container-shell flex min-h-[68vh] items-center justify-center py-12">
      <div className="grid w-full max-w-4xl overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-soft md:grid-cols-2">

        <div className="relative hidden min-h-[520px] overflow-hidden bg-sage-700 p-9 text-white md:block">
          <img
            src="https://images.unsplash.com/photo-1472396961693-142e6e269027?auto=format&fit=crop&w=1000&q=85"
            alt="Forest and mountain landscape"
            className="absolute inset-0 h-full w-full object-cover opacity-45"
          />

          <div className="absolute inset-0 bg-gradient-to-t from-[#343c2b]/90 to-[#343c2b]/10" />

          <div className="relative flex h-full flex-col justify-between">
            <Link to="/" className="flex items-center gap-2 font-bold">
              <Mountain /> hikingbuddy
            </Link>

            <div>
              <p className="eyebrow !text-white/70">FIND YOUR PEOPLE</p>

              <h1 className="mt-3 text-4xl font-black leading-tight">
                The trail is better together.
              </h1>

              <p className="mt-4 max-w-xs text-sm leading-6 text-white/80">
                Make plans, find your pace and explore somewhere new.
              </p>
            </div>

            <p className="text-xs text-white/60">
              Explore responsibly · Leave no trace
            </p>
          </div>
        </div>

        <div className="p-6 sm:p-10">
          <Link to="/" className="text-sm text-stone-400 hover:text-sage-700">
            ← Back to home
          </Link>

          <p className="eyebrow mt-8">
            {register ? 'JOIN THE COMMUNITY' : 'WELCOME BACK'}
          </p>

          <h2 className="mt-2 text-3xl font-bold tracking-tight text-ink">
            {register ? 'Create an account' : 'Sign in'}
          </h2>

          <p className="mt-2 text-sm text-stone-500">
            {register
              ? 'Your next outdoor story starts here.'
              : 'Your hiking buddies are waiting.'}
          </p>

          <form onSubmit={submit} className="mt-7 space-y-4">

            {register && (
              <Field
                label="Full name"
                name="name"
                value={form.name}
                onChange={change}
                placeholder="Your name"
                required
              />
            )}

            <Field
              label="Email address"
              type="email"
              name="email"
              value={form.email}
              onChange={change}
              placeholder="name@example.com"
              required
            />

            <label className="field-label">
              Password

              <div className="relative mt-2">
                <LockKeyhole
                  size={16}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"
                />

                <input
                  className="field !pl-10"
                  type="password"
                  name="password"
                  value={form.password}
                  onChange={change}
                  placeholder="At least 8 characters"
                  minLength={8}
                  required
                  autoComplete={register ? 'new-password' : 'current-password'}
                />
              </div>
            </label>

            {register && (
              <label className="field-label">
                Confirm password

                <div className="relative mt-2">
                  <LockKeyhole
                    size={16}
                    className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"
                  />

                  <input
                    className="field !pl-10"
                    type="password"
                    name="password_confirmation"
                    value={form.password_confirmation}
                    onChange={change}
                    placeholder="Enter your password again"
                    minLength={8}
                    required
                    autoComplete="new-password"
                  />
                </div>
              </label>
            )}

            {register && (
              <Field
                label="Home region"
                name="city"
                value={form.city}
                onChange={change}
                options={regions.slice(1)}
              />
            )}

            {error && (
              <p
                role="alert"
                className="rounded-lg bg-red-50 p-3 text-sm text-red-700"
              >
                {error}
              </p>
            )}

            <button
              className="btn-primary w-full justify-center disabled:opacity-60"
              type="submit"
              disabled={loading}
            >
              {loading
                ? 'Please wait...'
                : register
                  ? 'Create account'
                  : 'Sign in'}

              {!loading && <ArrowRight size={16} />}
            </button>
          </form>

          <p className="mt-6 text-center text-sm text-stone-500">
            {register ? 'Already a member?' : 'New to Hiking Buddy?'}{' '}

            <Link
              className="font-bold text-sage-700 hover:underline"
              to={register ? '/login' : '/register'}
            >
              {register ? 'Sign in' : 'Create an account'}
            </Link>
          </p>

          <p className="mt-5 text-xs leading-5 text-stone-400">
            Your account information is managed securely by Hiking Buddy.
          </p>
        </div>
      </div>
    </main>
  );
}

// --------------------------------------------------
// NOT FOUND
// --------------------------------------------------

function NotFound() {
  return (
    <main className="container-shell empty-state page-pad">
      <Mountain size={38} />
      <h1 className="mt-4 text-2xl font-bold">Trail not found</h1>

      <Link to="/trails" className="btn-primary mt-5">
        Back to trails
      </Link>
    </main>
  );
}

// --------------------------------------------------
// MAIN APP
// --------------------------------------------------

export default function App() {
  const [user, setUser] = useState(() => stored('hb-user', null));

  const [trips, setTrips] = useState(
    () => stored('hb-trips', initialTrips)
  );

  const [joined, setJoined] = useState(
    () => stored('hb-joined', [])
  );

  useEffect(() => {
    save('hb-trips', trips);
  }, [trips]);

  // Verify the saved login token with Laravel
useEffect(() => {
  const token = getAuthToken();

  if (!token) {
    localStorage.removeItem('hb-user');
    setUser(null);
    return;
  }

  fetch(`${API_BASE}/user`, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  })
    .then(async response => {
      if (!response.ok) {
        throw new Error('Session expired');
      }

      return response.json();
    })
    .then(data => {
      const currentUser = mapApiUser(data.user);

      localStorage.setItem('hb-user', JSON.stringify(currentUser));
      setUser(currentUser);
    })
    .catch(() => {
      localStorage.removeItem('hb-token');
      localStorage.removeItem('hb-user');
      setUser(null);
    });
}, []);

  const join = id => {
    if (!user) {
      window.location.href = '/login';
      return;
    }

    if (joined.includes(id)) return;

    const next = joined.concat(id);

    setJoined(next);
    save('hb-joined', next);

    setTrips(prev =>
      prev.map(trip =>
        trip.id === id
          ? {
              ...trip,
              joined: Math.min(
                Number(trip.spots) || 0,
                (Number(trip.joined) || 0) + 1
              )
            }
          : trip
      )
    );
  };

  const deleteTrip = id => {
    const trip = trips.find(item => item.id === id);

    if (!trip || trip.createdBy !== user?.email) {
      alert('You can only delete your own trips.');
      return;
    }

    if (!window.confirm('Are you sure you want to delete this trip?')) {
      return;
    }

    const updated = trips.filter(item => item.id !== id);

    setTrips(updated);
    save('hb-trips', updated);
  };

  const logout = async () => {
  const token = getAuthToken();

  try {
    if (token) {
      await fetch(`${API_BASE}/logout`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      });
    }
  } catch (error) {
    console.error('Logout request failed:', error);
  } finally {
    localStorage.removeItem('hb-token');
    localStorage.removeItem('hb-user');
    setUser(null);
  }
};

  return (
    <>
      <Header user={user} onLogout={logout} />

      <Routes>
        <Route
          path="/"
          element={
            <Home
              trips={trips}
              user={user}
              onJoin={join}
              joined={joined}
            />
          }
        />

        <Route
          path="/community"
          element={<Community user={user} />}
        />

        <Route
          path="/trails"
          element={
            <TrailsPage
              trips={trips}
              onJoin={join}
              joined={joined}
              user={user}
              setTrips={setTrips}
              onDeleteTrip={deleteTrip}
            />
          }
        />

        <Route
          path="/trails/:id"
          element={
            <TrailDetail
              trips={trips}
              onJoin={join}
              joined={joined}
              user={user}
              onDeleteTrip={deleteTrip}
            />
          }
        />

        {/* Old Post Your Trip URL redirects to the Trails page. */}
        <Route
          path="/post-trip"
          element={<Navigate to="/trails?create=true" replace />}
        />

        <Route
          path="/profile"
          element={
            <Profile
              user={user}
              setUser={setUser}
              trips={trips}
              onDeleteTrip={deleteTrip}
            />
          }
        />

        <Route
          path="/dashboard"
          element={
            <Dashboard
              user={user}
              trips={trips}
              setTrips={setTrips}
              onDeleteTrip={deleteTrip}
            />
          }
        />

        <Route
          path="/login"
          element={<AuthPage mode="login" setUser={setUser} />}
        />

        <Route
          path="/register"
          element={<AuthPage mode="register" setUser={setUser} />}
        />

        <Route path="*" element={<NotFound />} />
      </Routes>

      <Footer />
      <CookieBanner />
    </>
  );
}